<?php
/**
 * Some PHP utility functions for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2025, 2026 Claus-Justus Heine
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

namespace OCA\RotDrop\DevScripts\PhpToTypeScript;

use DateTime;
use DateTimeImmutable;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

use Spatie\TypeScriptTransformer\Collectors\EnumCollector;
use Spatie\TypeScriptTransformer\Structures\TransformedType;
use Spatie\TypeScriptTransformer\Transformers;
use Spatie\TypeScriptTransformer\TypeScriptTransformer;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;
use Spatie\TypeScriptTransformer\Types\TypeScriptType;

use OCP\AppFramework\IAppContainer;

use Carbon;
use Ramsey\Uuid\UuidInterface;

/**
 * Runner for the PHP to typescript conversion.
 */
class PhpToTypeScript extends Command
{
  private const OPTION_AS_MODULES = 'as-modules';
  private const OPTION_CONSTANTS = 'constants';
  private const OPTION_CONSTANTS_AS_CONSTANTS = 'constants';
  private const OPTION_CONSTANTS_AS_PROPERTIES = 'properties';
  private const OPTION_HELP = 'help';
  private const OPTION_NS_PREFIX = 'ns-prefix';
  private const OPTION_OUTPUT_PREFIX = 'output-prefix';
  private const OPTION_INPUT_PATHS = 'input-paths';
  private const OPTION_QUIET = 'quiet';
  private const OPTION_SCOPED_NS_PREFIX = 'scoped-ns-prefix';
  private const OPTION_SOURCE_DIR = 'source-dir';
  private const OPTION_EXCLUDE = 'exclude';
  private const OPTION_VERBOSE = 'verbose';
  private const OUTPUT_SUFFIX = '.d.ts';
  private const VERBOSITY_MAX = 3;
  private const LINE_SEPARATOR = "\r\n";
  private const LINE_BUFFER_SIZE = 4096;
  private const NS_DECLARATION = 'declare namespace';
  private const TYPE_DECLARATION = 'export type';
  private const ROOT_NS = 'ROOT';
  private const ROOT_MODULE = self::ROOT_NS . '.ts';
  private const PHP_PREFIX = 'php-';
  private const TS_MODULES_DIR = self::PHP_PREFIX . 'modules';
  private const TS_TYPES_FILE = self::PHP_PREFIX . 'types' . self::OUTPUT_SUFFIX;
  private const TRANSFORMERS = [
    Transformers\EnumTransformer::class,
    ClassConstantsTransformer::class,
    Transformers\DtoTransformer::class,
  ];

  /**
   * CTOR.
   *
   * @param string $devScriptsFolder The directory containing the development scripts.
   *
   * @param array $excludes Exluded directories, defaults to [].
   *
   * @param array $scopedNamespaces Scoped namespaces which need special
   * handling by the DatabaseEntityCollector class, defaults to [].
   */
  public function __construct(
    protected string $devScriptsFolder,
    protected array $excludes = [],
    protected array $scopedNamespaces = [],
  ) {
    parent::__construct();
  }

  /** {@inheritdoc} */
  protected function configure()
  {
    parent::configure();
    $this
      ->setName('PhpToTypeScript')
      ->setDescription('Generate TypeScript types from selected PHP sources.')
      ->addOption(
        self::OPTION_OUTPUT_PREFIX,
        'p',
        InputOption::VALUE_REQUIRED,
        'The path to the output directory. Required.',
      )
      ->addOption(
        self::OPTION_SOURCE_DIR,
        's',
        InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY,
        'The path to the source directory containing PHP files. Maybe given multiple times. Required.',
      )
      ->addOption(
        self::OPTION_EXCLUDE,
        'e',
        InputOption::VALUE_OPTIONAL|InputOption::VALUE_IS_ARRAY,
        'Exclude the given directory. Maybe given multiple times.',
        [],
      )
      ->addOption(
        self::OPTION_CONSTANTS,
        null,
        InputOption::VALUE_REQUIRED,
        'Emit constants as'
        . ' either literal type typed constants (--constants=' . self::OPTION_CONSTANTS_AS_CONSTANTS . ')'
        . ' or literal type typed properties (--constants=' . self::OPTION_CONSTANTS_AS_PROPERTIES . ').',
      )
      ->addOption(
        self::OPTION_NS_PREFIX,
        null,
        InputOption::VALUE_REQUIRED,
        'Specify a namespace prefix to remove, either in PHP notation or in TS notation.',
      )
      ->addOption(
        self::OPTION_SCOPED_NS_PREFIX,
        null,
        InputOption::VALUE_REQUIRED,
        'Specify the scoped namespace prefix of humbug/php-scoper. If --ns-prefix=NS_PREFIX was given then NS_PREFIX is prependet to the scoped namespace prefix.',
      )
      ->addOption(
        self::OPTION_AS_MODULES,
        null,
        InputOption::VALUE_NONE,
        'Convert the single-file namespace declaration to a multi-file module structure.',
      )
      ->addOption(
        self::OPTION_HELP,
        'h',
        InputOption::VALUE_NONE,
        'Display help',
      )
      ->addOption(
        self::OPTION_HELP,
        'h',
        InputOption::VALUE_NONE,
        'Display help',
      )
      ->addOption(
        self::OPTION_VERBOSE,
        'v|vv...',
        InputOption::VALUE_OPTIONAL|InputOption::VALUE_IS_ARRAY,
        'Set or increase verbosity level.',
        [],
      )
      ->addOption(
        self::OPTION_QUIET,
        'q',
        InputOption::VALUE_NONE,
        'Only emit output on errors.',
      )
      ;
  }

  /** {@inheritdoc} */
  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    if ($input->getOption(self::OPTION_HELP)) {
      $output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
      $help = new HelpCommand();
      $help->setCommand($this);
      $help->run($input, $output);
      return Command::SUCCESS;
    }
    $verbose = $input->getOption(self::OPTION_VERBOSE);
    $verbosity = 0;
    if ($verbose !== []) {
      $verbosity = array_reduce(
        $verbose,
        function(int $carry, null|int|string $level) {
          if (is_string($level)) {
            $level = strlen($level) + 1;
          }
          return $carry + ($level ?? 1);
        },
        0,
      );
      $verbosity = max($verbosity, self::VERBOSITY_MAX);
    }
    if ($input->getOption(self::OPTION_QUIET)) {
      $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
    } else {
      $output->setVerbosity(OutputInterface::VERBOSITY_NORMAL << $verbosity);
    }

    $error = false;
    $outputPrefix = $input->getOption(self::OPTION_OUTPUT_PREFIX);
    if (empty($outputPrefix)) {
      $output->writeln('<error>' . 'The "--' . self::OPTION_OUTPUT_PREFIX . '" option is mandatory.' . '</error>', OutputInterface::VERBOSITY_QUIET);
      $error = true;
    }
    $sourceDirs = $input->getOption(self::OPTION_SOURCE_DIR);
    if (empty($sourceDirs)) {
      $output->writeln('<error>' . 'The "--' . self::OPTION_SOURCE_DIR . '" option is mandatory.' . '</error>', OutputInterface::VERBOSITY_QUIET);
      $error = true;
    }
    $excludes = array_merge($input->getOption(self::OPTION_EXCLUDE), $this->excludes);
    if ($error) {
      $output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
      $help = new HelpCommand();
      $help->setCommand($this);
      $output->writeln('');
      $help->run($input, $output);
      return Command::INVALID;
    }

    if (!str_ends_with($outputPrefix, '/')) {
      $outputPrefix .= '/';
    }

    $namespacePrefix = $input->getOption(self::OPTION_NS_PREFIX);
    if (!empty($namespacePrefix)) {
      $namespacePrefix = trim($namespacePrefix, '\\');
      // convert from PHP to TypeScript notation.
      $tsNamespacePrefix = str_replace('\\', '.', $namespacePrefix);
      if (!str_ends_with($tsNamespacePrefix, '.')) {
        $tsNamespacePrefix .= '.';
      }
    } else {
      $tsNamespacePrefix = '';
    }

    $scopedNamespacePrefix = $input->getOption(self::OPTION_SCOPED_NS_PREFIX);
    if (!empty($scopedNamespacePrefix)) {
      $scopedNamespacePrefix = trim($namespacePrefix . '\\' . trim($scopedNamespacePrefix, '\\'), '\\');
    }

    $outputFile = $outputPrefix . self::TS_TYPES_FILE;

    $config = TransformerConfig::create()
      ->appNamespace($namespacePrefix)
      ->scopedNamespacePrefix($scopedNamespacePrefix)
      ->scopedNamespaces($this->scopedNamespaces)
      // path where your PHP classes are
      ->autoDiscoverTypes(...$sourceDirs)
      ->autoDiscoverExcludePaths(...$excludes)
      ->autoDiscoverExcludeRegExp('/.*~$|\\/\\.#.+/')
      ->nullToOptional(true)
      // ->transformToNativeEnums(true)
      // list of transformers
      ->transformers(self::TRANSFORMERS)
      ->collectors([
        // transform all abstract DTOs
        DTOCollector::class,
        // transform all native enums
        EnumCollector::class,
        // transfrom all database entities
        DatabaseEntityCollector::class,
      ])
      // try inject default TypeScriptTransformer
      ->defaultTypeReplacements([
        // Carbon actually just by default emits a simple strings
        // Carbon\CarbonImmutable::class => new TypeScriptType('{ date: string, timezone_type: number, timezone: string }'),
        // Carbon\Carbon::class => new TypeScriptType('{ date: string, timezone_type: number, timezone: string }'),
        Carbon\CarbonImmutable::class => new TypeScriptType('string'),
        Carbon\Carbon::class => new TypeScriptType('string'),
        DateTime::class => new TypeScriptType('{ date: string, timezone_type: number, timezone: string }'),
        DateTimeImmutable::class => new TypeScriptType('{ date: string, timezone_type: number, timezone: string }'),
        UuidInterface::class => new TypeScriptType('string'),
      ])
      // try inject default TypeScriptTransformer
      ->defaultInlineTypeReplacements([
        // 'mixed' => 'unknown',
        // 'array' => new TypeScriptType('Record<string|number, unknown>'),
        // Carbon\CarbonImmutable::class => new TypeScriptType('{ date: string, timezone_type: number, timezone: string }'),
        // Carbon\Carbon::class => new TypeScriptType('{ date: string, timezone_type: number, timezone: string }'),
        // UuidInterface::class => new TypeScriptType('string'),
      ])
      // file where TypeScript type definitions will be written
      ->outputFile($outputFile);

    switch ($input->getOption(self::OPTION_CONSTANTS)) {
      case self::OPTION_CONSTANTS_AS_CONSTANTS:
        $config->constantsAsConstants(true);
        break;
      case self::OPTION_CONSTANTS_AS_PROPERTIES:
        $config->constantsAsProperties(true);
        break;
      default:
        $config->constantsAsConstants(true);
        break;
    }

    $types = TypeScriptTransformer::create($config)->transform();

    if (!empty($tsNamespacePrefix)) {
      $output->writeln('<info>' . 'Stripping namespace ' . $tsNamespacePrefix . '</>');
      $this->fixupTypeScriptTransformer($tsNamespacePrefix, $outputFile, $output);
    }

    if ($input->getOption(self::OPTION_AS_MODULES)) {
      $metadataGenerator = new GenerateEntityMetadata(
        phpNamespacePrefix: $input->getOption(self::OPTION_NS_PREFIX),
        outputPrefix: $outputPrefix . self::TS_MODULES_DIR,
        output: $output,
        devScriptsFolder: $this->devScriptsFolder,
      );
      $metadataGenerator->generateSparseMetadata();
      $entityMapNamespace = $metadataGenerator->exportEntityMap();
      $tsData = file_get_contents($outputFile);
      $tsData = $entityMapNamespace . "\n" . $tsData;
      file_put_contents($outputFile, $tsData);

      $this->generateTypeScriptModules($outputPrefix, $outputFile, $output);

      $metadataGenerator->dumpTypeScriptData();
    }

    if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
      $output->writeln('');
      $output->writeln('<info> *** ' . $outputName . ' *** </info>');
      /** @var TransformedType $type */
      foreach ($types as $class => $type) {
        $output->writeln('<info>' . $class . ' -> ' . $type->getTypeScriptName() . '</info>');
      }
      $output->writeln('---------------------------------------------');
      $output->writeln('');
    }

    return Command::SUCCESS;
  }

  /**
   * @param string $tsNamespacePrefix
   *
   * @param string $outputFile
   *
   * @param ConsoleOutputInterface $output
   *
   * @return void
   */
  private function fixupTypeScriptTransformer(
    string $tsNamespacePrefix,
    string $outputFile,
    ConsoleOutputInterface $output,
  ): void {
    // strip the top-level namespace as requested and record "root" data types.
    $tsData = str_replace($tsNamespacePrefix, '', file_get_contents($outputFile));

    // fixup [key: EnumType]
    //
    // TS error 1337 "... consider using a mapped type instead"
    //
    // This is difficult to handle inside Spatie\TypeScriptTransformer ... we
    // exploit the fact that our Enum-types have an "Enum" in their name. This
    // is kludgy, but should work.
    //
    // e.g.
    // insuranceRates: { [key: Types.EnumGeographicalScope]: InsuranceRate };
    $tsFile = fopen('php://temp', 'r+');
    fputs($tsFile, $tsData);
    rewind($tsFile);
    $line = fgets($tsFile, self::LINE_BUFFER_SIZE);
    $tsData = '';
    while ($line !== false) {
      $line = preg_replace('/\\[([[:alnum:]]+):\s*([^]]*Enum[^]]*)\\]/', '[$1 in $2]', $line);
      $tsData .= $line . "\n";
      $line = fgets($tsFile, self::LINE_BUFFER_SIZE);
    }
    fclose($tsFile);
    file_put_contents($outputFile, $tsData);
  }

  /**
   * @param string $outputPrefix
   *
   * @param string $outputFile
   *
   * @param OutputInterface|ConsoleSectionOutput $output
   *
   * @return void
   */
  private function generateTypeScriptModules(
    string $outputPrefix,
    string $outputFile,
    ConsoleOutputInterface $output,
  ): void {
    $headerSection = $output->section();
    $textSection = $output->section();
    $textSection->setMaxHeight(5);
    $progressSection = $output->section();
    $generator = basename(__FILE__);
    $modulesDir = $outputPrefix . '/' . self::TS_MODULES_DIR . '/';
    mkdir($modulesDir);
    $tsData = file_get_contents($outputFile);
    $topLevelTypes = [];
    $currentModule = null;
    $currentFullNS = null;
    $allNamespaces = [];
    $headerData = [];
    $currentData = null;
    $templateString = false;
    $tsFile = fopen('php://temp', 'r+');
    fputs($tsFile, $tsData);
    // First scan: collect all namespaces
    $numberOfLines = substr_count($tsData, PHP_EOL);
    $headerSection->writeln('<info>' . 'Scanning for namespaces ...' . '</>');
    $progressBar = new ProgressBar($progressSection);
    $progressBar->start($numberOfLines);
    rewind($tsFile);
    $line = fgets($tsFile, self::LINE_BUFFER_SIZE);
    while ($line !== false) {
      $progressBar->advance();
      $line = rtrim($line, PHP_EOL);
      $backticksCount = substr_count($line, '`');
      if (!$templateString && $backticksCount == 0) {
        if (str_starts_with($line, self::NS_DECLARATION)) {
          $namespaces = explode('.', trim(substr($line, strlen(self::NS_DECLARATION)), ' {'));
          $currentFullNS = implode('.', $namespaces);
          $textSection->writeln('Current FQ Namespace ' . $currentFullNS, options: OutputInterface::VERBOSITY_NORMAL);
          $allNamespaces[] = $currentFullNS;
          $allNamespaces = array_values(array_unique($allNamespaces));
        } elseif (str_starts_with($line, self::TYPE_DECLARATION)) {
          [,, $type] = explode(' ', $line);
          [, $typeDefinition] = explode('=', $line);
          $topLevelTypes[$type] = $typeDefinition;
        }
      } elseif ($templateString) {
        $templateString = $backticksCount % 2 == 0;
      } else {
        $templateString = $backticksCount % 2 == 1;
      }
      $line = fgets($tsFile, self::LINE_BUFFER_SIZE);
    }
    if (!empty($topLevelTypes)) {
      $allNamespaces[] = self::ROOT_NS;
    }
    // Second run: emit typedefs, replace namespaces as appropriate
    $templateString = false;
    $currentFullNS = null;

    $headerSection->writeln('<info>' . 'Adjusting namespace references ...' . '</>');
    $progressBar->start($numberOfLines);
    rewind($tsFile);
    $line = fgets($tsFile, self::LINE_BUFFER_SIZE);
    while ($line !== false) {
      $progressBar->advance();
      $line = rtrim($line, PHP_EOL);
      $backticksCount = substr_count($line, '`');
      if (!$templateString && $backticksCount == 0) {
        if (str_starts_with($line, self::NS_DECLARATION)) {
          $namespaces = explode('.', trim(substr($line, strlen(self::NS_DECLARATION)), ' {'));
          $currentFullNS = implode('.', $namespaces);
          $modulesPath = $modulesDir;
          while (!empty($namespaces)) {
            $currentNs = array_shift($namespaces);
            if (!empty($namespaces)) {
              // emit trampoline modules
              $nextNs = reset($namespaces);
              $currentModule = $modulesPath . $currentNs . '.ts';
              $newData = "export * as {$nextNs} from './{$currentNs}/{$nextNs}.ts';";
              $currentData = file_get_contents($currentModule);
              if (!empty($currentData) && !str_contains($currentData, $newData)) {
                $currentData .= $newData . PHP_EOL;
              } elseif (empty($currentData)) {
                $currentData = <<<EOF
// Automatically generated by {$generator}, do not edit!


EOF;
                $currentData .= $newData . PHP_EOL;
              }
              file_put_contents($currentModule, $currentData);
              $modulesPath .= $currentNs . '/';
              mkdir($modulesPath);
            } else {
              // emit the current's namespace module
              $currentModule = $modulesPath . $currentNs . '.ts';
              $currentData = '';
              $headerData[] = <<<EOF
// Automatically generated by {$generator}, do not edit!


EOF;
            }
          }
        } elseif ($currentFullNS && $line == '}') {
          if ($currentData && $currentModule) {
            $headerData = array_values(array_unique($headerData));
            sort($headerData);
            $currentData = implode(PHP_EOL, $headerData) . PHP_EOL . PHP_EOL .  $currentData;
            file_put_contents($currentModule, $currentData);
            $currentData = $currentModule = null;
          }
          $currentFullNS = null;
          $headerData = [];
        } else {
          [,$typeSpec] = explode(':', $line);
          foreach (array_keys($topLevelTypes) as $type) {
            if (preg_match('/[[:^alnum:]]' . $type . '[[:^alnum:]]/', $typeSpec)) {
              $line = str_replace($type, self::ROOT_NS . '.' . $type, $line);
            }
          }
          $line = str_replace($currentFullNS . '.', '', $line);

          foreach ($allNamespaces as $existingNamespace) {
            // if (preg_match('/[[:^alnum:]]' . preg_quote($existingNamespace . '.') . '/', $typeSpec)) {
            if (str_starts_with($existingNamespace, $currentFullNS) && $existingNamespace != $currentFullNS) {
              $namespaceForMatch = substr($existingNamespace, strlen($currentFullNS) + 1);
            } else {
              $namespaceForMatch = $existingNamespace;
            }
            if (preg_match('/(([^:]+):|export type)(.*)([[:^alnum:]])(' . preg_quote($namespaceForMatch . '.') . ')/', $line)) {
              $selfNS = explode('.', $currentFullNS);
              $refNS = explode('.', $existingNamespace);
              $textSection->writeln('CROSSREF ' . $currentFullNS . ' ' . $existingNamespace, options: OutputInterface::VERBOSITY_NORMAL);
              $prefix = [];
              // Database.Doctrine.ORM.EntityMetadata
              // Database.Doctrine.ORM.Util
              // Database.Doctrine.ORM.EntityMetadata.EntityMap
              //
              //  Wrapped.Carbon.CarbonImmutable
              //  Database.Doctrine.ORM.Entities
              while (!empty($selfNS) && !empty($refNS) && reset($selfNS) == reset($refNS)) {
                array_shift($selfNS);
                $importNamespace = array_shift($refNS);
                $prefix[] = $importNamespace;
              }
              // do {
              //   array_shift($selfNS);
              //   $importNamespace = array_shift($refNS);
              //   $prefix[] = $importNamespace;
              // } while (!empty($selfNS) && !empty($refNS) && reset($selfNS) == reset($refNS));
              // EntityMetadata
              // Util
              // prefix = [ Database, Doctrine, ORM ]
              //
              //
              if (!empty($selfNS) && !empty($refNS)) {
                // can move one level further down.
                array_shift($selfNS);
                $importNamespace = array_shift($refNS);
              } else {
                array_pop($prefix);
              }
              if (empty($prefix)) {
                $erasePrefix = '';
              } else {
                $erasePrefix = implode('.', $prefix) . '.';
              }
              $upFolders = str_repeat('../', count($selfNS));
              $importPath = "./{$upFolders}{$importNamespace}";
              while (!empty($refNS)) {
                $erasePrefix .= $importNamespace . '.';
                $importNamespace = array_shift($refNS);
                $importPath .= '/' . $importNamespace;
              }
              $line = str_replace($erasePrefix . $importNamespace . '.', $importNamespace . '.', $line);
              $headerData[] = "import type * as {$importNamespace} from '{$importPath}.ts';";
            }
          }
          $currentData .= $line . PHP_EOL;
        }
      } elseif ($templateString) {
        // just write the line as is
        $currentData .= $line . PHP_EOL;
        $templateString = $backticksCount % 2 == 0;
      } else {
        $currentData .= substr($line, 2) . PHP_EOL;
        $templateString = $backticksCount % 2 == 1;
      }
      $line = fgets($tsFile, self::LINE_BUFFER_SIZE);
    }
    fclose($tsFile);
    if (!empty($currentData)) {
      // Top-level types. We assume these come last, if this changes
      // _this_ code thas to be adjusted.
      file_put_contents($modulesDir . self::ROOT_MODULE, $currentData);
    }
  }
}
