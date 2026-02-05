<?php
/**
 * Some PHP utility functions for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2022-2025 Claus-Justus Heine <himself@claus-justus-heine.de>
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\RotDrop\Toolkit\Service;

use Throwable;
use RuntimeException;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception as ProcessExceptions;

use OCP\Files\IMimeTypeDetector;
use OCP\IL10N;
use OCP\ITempManager;
use Psr\Log\LoggerInterface as ILogger;

use OCA\RotDrop\Toolkit\Exceptions;

/**
 * A class which can convert "any" (read: some) file-data to PDF format.
 * Currently anything supported by LibreOffice via unoconv and .eml via
 * mhonarc will work.
 */
class AnyToPdf
{
  use \OCA\RotDrop\Toolkit\Traits\LoggerTrait;

  const UNIVERSAL = '[universal]';
  const FALLBACK = '[fallback]';
  const PASS_THROUGH = '[pass-through]';

  private const DEFAULT_FALLBACK_CONVERTERS = ['unoconvert', 'unoconv'];

  /**
   * @var string Array of available converters per mime-type. The converters
   * form a chain of alternatives. The first non-failing alternative wins in
   * the given order.
   */
  const CONVERTERS = [
    'message/rfc822' => [
      [ [ 'mhonarc', ], [ 'wkhtmltopdf', 'weasyprint', 'pandoc:pdf', self::FALLBACK, ], ],
    ],
    'application/postscript' => [
      [ [ 'ps2pdf', ], ],
    ],
    'image/jpeg' => [
      [ [ 'img2pdf', self::FALLBACK, ], ],
    ],
    'image/tiff' => [
      [ [ 'tiff2pdf', ], ],
    ],
    'text/html' => [
      [ [ 'wkhtmltopdf', 'weasyprint', 'pandoc:pdf', self::FALLBACK, ], ],
    ],
    'text/markdown' => [
      [ [ 'pandoc:pdf', ], ],
      [ [ 'pandoc:html', ], [ 'wkhtmltopdf', 'weasyprint', 'pandoc:pdf', self::FALLBACK, ], ],
    ],
    'application/pdf' => [
      [ [ self::PASS_THROUGH, ], ],
    ]
  ];

  /**
   * @var int
   * Unoconv sometimes failes for no good reason and succeeds on the second try ...
   */
  private const UNOCONV_RETRIES = 3;

  /** @var IMimeTypeDetector */
  protected $mimeTypeDetector;

  /** @var ITempManager */
  protected $tempManager;

  /** @var IL10N */
  protected $l;

  /**
   * @var string
   * @todo Make it configurable
   * Paper size for converters which need it.
   */
  protected $paperSize = 'a4';

  /** @var ExecutableFinder */
  protected $executableFinder;

  /** @var string */
  protected $fallbackConverter;

  /** @var string */
  protected $universalConverter;

  /** @var bool */
  protected $builtinConvertersDisabled;

  /**
   * @var array
   * Cache of found executables for the current request.
   */
  protected $executables = [];

  /**
   * @param IMimeTypeDetector $mimeTypeDetector
   * @param ITempManager $tempManager
   * @param ExecutableFinder $executableFinder
   * @param ILogger $logger
   * @param IL10N $l10n
   */
  public function __construct(
    IMimeTypeDetector $mimeTypeDetector,
    ITempManager $tempManager,
    ExecutableFinder $executableFinder,
    ILogger $logger,
    IL10N $l10n
  ) {
    $this->mimeTypeDetector = $mimeTypeDetector;
    $this->tempManager = $tempManager;
    $this->executableFinder = $executableFinder;
    $this->logger = $logger;
    $this->l = $l10n;

    $this->setFallbackConverter(null);
  }

  /**
   * @return null|string The currently set fallback converter.
   */
  public function getDefaultFallbackConverter():?string
  {
    return $this->fallbackConverter ?? null;
  }

  /**
   * Install a fall-back converter script.
   *
   * @param null|string $converter The full path to the converter executatble
   *                               or null in order to reinstall the default.
   *
   * @return AnyToPdf Return $this for chainging.
   */
  public function setFallbackConverter(?string $converter):AnyToPdf
  {
    if (empty($converter)) {
      foreach (self::DEFAULT_FALLBACK_CONVERTERS as $converter) {
        try {
          $this->findExecutable($converter);
          $this->fallbackConverter = $converter;
          return $this;
        } catch (Throwable $t) {
          $this->logDebug('Unable to find "' . $converter . '".');
        }
      }
      throw new Exceptions\EnduserNotificationException(
        $this->l->t(
          'Unable to find any of the fallback converters "%s".',
          implode('", "', self::DEFAULT_FALLBACK_CONVERTERS),
        ),
      );
    }
    $this->fallbackConverter = $converter;
    return $this;
  }

  /**
   * Return the currently installed fallback-converter.
   *
   * @return string
   */
  public function getFallbackConverter():string
  {
    return $this->fallbackConverter;
  }

  /**
   * Set an "universal" converter executable to try before all others.
   *
   * @param null|string $converter The full path to the converter executable
   * or null in order to disable it.
   *
   * @return AnyToPdf Return $this for chaining purposes.
   */
  public function setUniversalConverter(?string $converter):AnyToPdf
  {
    $this->universalConverter = $converter;
    return $this;
  }

  /**
   * Return the currently installed universal converter executable (maybe
   * null).
   *
   * @return null|string
   */
  public function getUniversalConverter():?string
  {
    return $this->universalConverter;
  }

  /**
   * Disable the builtin converters.
   *
   * @return AnyToPdf Return $this for chaining purposes.
   */
  public function disableBuiltinConverters():AnyToPdf
  {
    $this->builtinConvertersDisabled = true;
    return $this;
  }

  /**
   * Enable the builtin converters.
   *
   * @return AnyToPdf Return $this for chaining purposes.
   */
  public function enableBuiltinConverters():AnyToPdf
  {
    $this->builtinConvertersDisabled = false;
    return $this;
  }

  /**
   * Return the current state of using the builtin converters.
   *
   * @return bool The disabled state of the builtin-converters.
   */
  public function builtinConvertersDisabled():bool
  {
    return !empty($this->builtinConvertersDisabled);
  }

  /**
   * Diagnose the state of the builtin-converter chains, i.e. try to find the
   * binaries.
   *
   * @return array
   */
  public function findConverters():array
  {
    $result = [];

    if (!empty($this->universalConverter)) {
      $executable = $this->executableFinder->find($this->universalConverter, force: true);
      if (empty($executable)) {
        $executable = $this->l->t('not found');
      }
      $result[self::UNIVERSAL] = [ [ $this->universalConverter => $executable ] ];
    }
    if ($this->builtinConvertersDisabled) {
      return $result;
    }
    $result = [];
    foreach (self::CONVERTERS as $mimeType => $chainAlternatives) {
      foreach ($chainAlternatives as $converterChain) {
        $probedChain = [];
        foreach ($converterChain as $converters) {
          $probedConverters = [];
          foreach ($converters as $converter) {
            if ($converter == self::PASS_THROUGH) {
              // TRANSLATORS: This is actually just the name of the "converter"
              // TRANSLATORS: which does "nothing", i.e. just copies the input
              // TRANSLATORS: data unchanged to the output.
              $probedConverters[$converter] = $this->l->t('pass through');
              continue;
            }
            if ($converter == self::FALLBACK) {
              $converter = $this->fallbackConverter;
            }
            list($programName,) = explode(':', $converter);
            try {
              $executable = $this->executableFinder->find($programName, force: true);
            } catch (Exceptions\EnduserNotificationException $e) {
              $this->logException($e);
              $executable = null;
            }
            if (empty($executable)) {
              $executable = $this->l->t('not found');
            }
            $probedConverters[$converter] = $executable;
          }
          $probedChain[] = $probedConverters;
        }
        $result[] = [
          'mimeType' => $mimeType,
          'chain' => $probedChain,
        ];
      }
    }
    try {
      $executable = $this->executableFinder->find($this->fallbackConverter, force: true);
    } catch (Exceptions\EnduserNotificationException $e) {
      $this->logException($e);
    }
    if (empty($executable)) {
      $executable = $this->l->t('not found');
    }
    $result[] = [
      'mimeType' => self::FALLBACK,
      'chain' => [ [ $this->fallbackConverter => $executable ], ],
    ];
    return $result;
  }

  /**
   * Try to convert the given data-block $data to PDF using any of the known
   * converters. If no converter can do the job provide an error-page with
   * information in PDF format.
   *
   * @param string $data Data-block to be converted.
   *
   * @param string|null $mimeType If null or 'application/octet-stream' the
   * cloud's mime-type detector is used to detect the mime-type.
   *
   * @return string The converted data.
   */
  public function convertData(string $data, ?string $mimeType = null):string
  {
    if (empty($mimeType) || $mimeType == 'application/octet-stream') {
      $mimeType = $this->mimeTypeDetector->detectString($data);
    }

    if (!empty($this->universalConverter)) {
      try {
        $data = $this->genericConvert($data, $mimeType, $this->universalConverter);
      } catch (Throwable $t) {
        if ($this->builtinConvertersDisabled) {
          throw new RuntimeException(
            $this->l->t('Universal converter "%1$s" has failed trying to convert MIME type "%2$s"', [
              $this->universalConverter, $mimeType,
            ]),
            0,
            $t,
          );
        } else {
          $this->logException($t, 'Ignoring failed universal converter ' . $this->universalConverter);
        }
      }
    }

    $chains = self::CONVERTERS[$mimeType] ?? [ [ [ self::FALLBACK, ], ], ];

    $originalData = $data;
    foreach ($chains as $chain) {
      foreach ($chain as $subStep) {
        $convertedData = null;
        foreach ($subStep as $tryConverter) {
          if ($tryConverter == self::FALLBACK) {
            $tryConverter = $this->fallbackConverter;
          } elseif ($tryConverter == self::PASS_THROUGH) {
            $tryConverter = 'passThrough';
          }
          $tryConverter = lcfirst(implode(array_map(fn($part) => ucfirst($part), explode(':', $tryConverter))));
          try {
            $method = $tryConverter . 'Convert';
            if (method_exists($this, $method)) {
              $convertedData = $this->$method($data);
            } else {
              $convertedData = $this->genericConvert($data, $mimeType, $tryConverter);
            }
            break;
          } catch (Throwable $t) {
            $this->logException($t, 'Failed converter ' . $tryConverter);
            $convertedData = null;
          }
        }
        if (empty($convertedData)) {
          $this->logError('Converter chain substep for ' . $mimeType . ' has failed: ' . print_r($subStep, true));
          $data = $originalData;
          break; // no chance to continue, but perhaps there is an alternative
        }
        $data = $convertedData;
      }
      if (!empty($convertedData)) {
        break;
      }
    }
    if (empty($convertedData)) {
      throw new RuntimeException(
        $this->l->t('Converter "%1$s" has failed trying to convert MIME type "%2$s"', [
          print_r($chains, true), $mimeType,
        ]),
      );
    }

    return $data;
  }

  /**
   * Do-nothing pass-through converter.
   *
   * @param string $data Original data.
   *
   * @return string Converted-to-PDF data.
   */
  protected function passThroughConvert(string $data):string
  {
    return $data;
  }

  /**
   * Generic conversion for given mime-type and converter script.
   *
   * @param string $data Original data.
   *
   * @param string $mimeType The detected mime-type of the data.
   *
   * @param string $converterName The name of the executable. Must be either
   * the full path or contained in the search-path for executables.
   *
   * @return string Converted-to-PDF data.
   */
  protected function genericConvert(string $data, string $mimeType, string $converterName):string
  {
    $converter = $this->findExecutable($converterName);
    $process = new Process([
      $converter,
      '--mime-type=' . $mimeType,
    ]);
    $process->setInput($data)->run();
    return $process->getOutput();
  }

  /**
   * Convert using unoconvert service (based on LibreOffice). This is the
   * successor of unoconv and actively maintained. It is not yet available as
   * package, so it requires more hand-work to install it.
   *
   * @param string $data Original data.
   *
   * @return string Converted-to-PDF data.
   */
  protected function unoconvertConvert(string $data):string
  {
    $converterName = 'unoconvert';
    $converter = $this->findExecutable($converterName);
    $retry = false;
    $count = 0;
    do {
      $process = new Process([
        $converter,
        '--convert-to', 'pdf',
        '--filter-options', 'ExportNotes=false',
        '--filter-options', 'ExportFormFields=true',
        '-', '-',
      ]);
      $process->setInput($data);
      try {
        $process->run();
        $retry = false;
      } catch (ProcessExceptions\ProcessTimedOutException $timedOutException) {
        $this->logException($timedOutException, 'Unrecoverable exception');
        $retry = false;
      } catch (Throwable $t) {
        $this->logException($t, 'Retry after exception, trial number ' . ($count + 1));
        $retry = true;
      }
    } while ($retry && $count++ < self::UNOCONV_RETRIES);

    return $process->getOutput();
  }

  /**
   * Convert using unoconv service (based on LibreOffice).
   *
   * @param string $data Original data.
   *
   * @return string Converted-to-PDF data.
   */
  protected function unoconvConvert(string $data):string
  {
    $converterName = 'unoconv';
    $converter = $this->findExecutable($converterName);
    $retry = false;
    $count = 0;
    do {
      $process = new Process([
        $converter,
        '-f', 'pdf',
        '--stdin', '--stdout',
        '-e', 'ExportNotes=False'
      ]);
      $process->setInput($data);
      try {
        $process->run();
        $retry = false;
      } catch (ProcessExceptions\ProcessTimedOutException $timedOutException) {
        $this->logException($timedOutException, 'Unrecoverable exception');
        $retry = false;
      } catch (Throwable $t) {
        $this->logException($t, 'Retry after exception, trial number ' . ($count + 1));
        $retry = true;
      }
    } while ($retry && $count++ < self::UNOCONV_RETRIES);

    return $process->getOutput();
  }

  /**
   * Convert using mhonarc.
   *
   * @param string $data Original data.
   *
   * @return string Converted-to-HTML data.
   */
  protected function mhonarcConvert(string $data):string
  {
    $converterName = 'mhonarc';
    $converter = $this->findExecutable($converterName);
    $attachmentFolder = $this->tempManager->getTemporaryFolder();
    $rcFile = $this->tempManager->getTemporaryFile();
    file_put_contents($rcFile, '<-- Make sure lines are no longer than 80 characters -->
<MIMEArgs>
m2h_text_plain::filter; maxwidth=80
m2h_text_plain::filter; nonfixed
</MIMEArgs>
');
    $process = new Process([
      $converter,
      '-single',
      '-attachmentdir', $attachmentFolder,
      '-rcfile', $rcFile,
    ]);
    $process->setInput($data)->run();
    $htmlData = $process->getOutput();
    $replacements = [];
    foreach (scandir($attachmentFolder) as $dirEntry) {
      if (str_starts_with($dirEntry, '.')) {
        continue;
      }
      $attachmentData = file_get_contents($attachmentFolder . '/' . $dirEntry);
      $mimeType = $this->mimeTypeDetector->detectString($attachmentData);
      $dataUri = 'data:' . $mimeType . ';base64,' . base64_encode($attachmentData);
      $replacements[$dirEntry] = $dataUri;
      // $this->logInfo('ATTACHMENT ' . $dirEntry . ' -> ' . $dataUri);
      //
      // src="./jpg6CyWjpSPxE.jpg"
    }
    $htmlData = str_replace(array_keys($replacements), array_values($replacements), $htmlData);

    return $htmlData;
  }

  /**
   * Convert using ps2pdf.
   *
   * @param string $data Original data.
   *
   * @return string Converted-to-PDF data.
   */
  protected function ps2pdfConvert(string $data):string
  {
    $converterName = 'ps2pdf';
    $converter = $this->findExecutable($converterName);
    $process = new Process([
      $converter,
      '-', '-',
    ]);
    $process->setInput($data)->run();
    return $process->getOutput();
  }

  /**
   * Convert using weasyprint
   *
   * @param string $data Original data.
   *
   * @return string Converted-to-PDF data.
   */
  protected function weasyprintConvert(string $data):string
  {
    $converterName = 'weasyprint';
    $converter = $this->findExecutable($converterName);
    $process = new Process([
      $converter,
      '-', '-',
    ]);
    $process->setInput($data)->run();
    return $process->getOutput();
  }

  /**
   * Convert using wkhtmltopdf.
   *
   * @param string $data Original data.
   *
   * @return string Converted-to-PDF data.
   */
  protected function wkhtmltopdfConvert(string $data):string
  {
    $converterName = 'wkhtmltopdf';
    $converter = $this->findExecutable($converterName);
    $process = new Process([
      $converter,
      '-', '-',
    ]);
    $process->setInput($data)->run();
    return $process->getOutput();
  }

  /**
   * Convert to html using pandoc
   *
   * @param string $data Original data.
   *
   * @return string Converted-to-PDF data.
   */
  protected function pandocPdfConvert(string $data):string
  {
    return $this->pandocConvert($data, [ '-t', 'pdf', '-V', 'geometry:a4paper,margin=2cm' ]);
  }

  /**
   * Convert to html using pandoc
   *
   * @param string $data Original data.
   *
   * @return string Converted-to-PDF data.
   */
  protected function pandocHtmlConvert(string $data):string
  {
    return $this->pandocConvert($data, ['-t', 'html' ]);
  }

  /**
   * Convert to html using pandoc
   *
   * @param string $data Original data.
   *
   * @param array $options
   *
   * @return string Converted-to-PDF data.
   */
  protected function pandocConvert(string $data, array $options):string
  {
    $converterName = 'pandoc';
    $converter = $this->findExecutable($converterName);
    $process = new Process(array_merge([
      $converter,
      '-s',
    ], $options));
    $process->setInput($data)->run();
    return $process->getOutput();
  }

  /**
   * Convert using tiff2pdf.
   *
   * @param string $data Original data.
   *
   * @return string Converted-to-PDF data.
   */
  protected function tiff2pdfConvert(string $data):string
  {
    $converterName = 'tiff2pdf';
    $converter = $this->findExecutable($converterName);
    $inputFile = $this->tempManager->getTemporaryFile();
    $outputFile = $this->tempManager->getTemporaryFile();
    file_put_contents($inputFile, $data);

    // As of mow tiff2pdf cannot write to stdout.
    $process = new Process([
      $converter,
      '-p', $this->paperSize,
      '-o', $outputFile,
      $inputFile,
    ]);
    $process->run();
    $data = file_get_contents($outputFile);

    unlink($inputFile);
    unlink($outputFile);
    return $data;
  }

  /**
   * Convert using img2pdf.
   *
   * @param string $data Original data.
   *
   * @return string Converted-to-PDF data.
   */
  protected function img2pdfConvert(string $data):string
  {
    putenv('LC_ALL=C');
    $converterName = 'img2pdf';
    $converter = $this->findExecutable($converterName);

    try {
      // we actually want to have "--rotation=ifvalid", see issue #100 on https://gitlab.mister-muffin.de/josch/img2
      $process = new Process([ $converter, '--version' ]);
      $process->run();
      $versionString = $process->getOutput();
      // img2pdf 0.4.4
      $matches = null;
      if (preg_match('/' . $converterName . '\\s+(\\d+)\\.(\\d+)\\.(\\d+)/', $versionString, $matches)) {
        $version = [
          'major' => $matches[1],
          'minor' => $matches[2],
          'patch' => $matches[3],
        ];
      }
    } catch (Throwable $t) {
      throw new RuntimeException(
        $this->l->t('Unable to determine the version of the helper program "%1$s", is it installed?', $converter), 0, $t,
      );
    }
    $processAndArgs = [
      $converter,
      '-', // from stdin
    ];
    if ($version['minor'] >= 4 && $version['patch'] >= 4) {
      $processAndArgs[] = '--rotation=ifvalid'; // ignore broken rotation settings in EXIF meta data
    }
    $process = new Process($processAndArgs);
    $process->setInput($data)->run();
    return $process->getOutput();
  }

  /**
   * Try to find the given executable.
   *
   * @param string $program The program to search for. This must be the
   * basename of a Un*x program.
   *
   * @return string The full path to $program.
   *
   * @throws Exceptions\EnduserNotificationException
   */
  protected function findExecutable(string $program):string
  {
    return $this->executableFinder->find($program, force: false);
  }
}
