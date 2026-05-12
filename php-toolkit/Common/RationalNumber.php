<?php
/**
 * Some PHP utility functions for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2026 Claus-Justus Heine <himself@claus-justus-heine.de>
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

namespace OCA\RotDrop\Toolkit\Common;

use Spatie\TypeScriptTransformer\Attributes as TSAttributes;
use Spatie\TypeScriptTransformer\Transformers\DtoTransformer;

use InvalidArgumentException;
use JsonSerializable;
use OutOfBoundsException;

use MathPHP\Number\Rational;

/**
 * Rational numbers, exact fractions. This is mainly useful in a context where
 * the possible denominator are well known, which often is the case in a
 * monetary context.
 */
#[TSAttributes\TypeScript]
#[TSAttributes\LiteralTypeScriptType('string')]
#[TSAttributes\TypeScriptTransformer(DtoTransformer::class)]
#[Attributes\UsesClass(\OCA\CAFEVDB\Common\RationalNumber::class)]
class RationalNumber extends Rational implements JsonSerializable
{
  /**
   * @var int
   *
   * Maximum number of digits to procuce with RationalNumber::toDecimal(scale: -1).
   *
   * This MUST NOT BE TOO LARGE as otherwise PHP implicitly casts numerical
   * results to float.
   */
  public const DECIMAL_DIGITS_MAX = 15;

  /**
   * {@inheritdoc}
   *
   * @param bool $normalized Assume the three ingredients do not need normalization.
   */
  public function __construct(int $integralPart, int $numerator = 0, int $denominator = 1, bool $skipNormalization = false)
  {
    if ($skipNormalization) {
      $this->whole = $integralPart;
      $this->numerator = $numerator;
      $this->denominator = $denominator;
    } else {
      parent::__construct($integralPart, $numerator, $denominator);
    }
  }

  /**
   * Generator method. If called with only one argument try to gracefully
   * convert the argument to RationalNumber. Passing even null as first
   * argument will create a representation of zero as RationalNumber. Empty
   * strings will also generate a zero.
   *
   * @param int|float|string|RationalNumber $integralPartOrAny
   *
   * @param null|int $numerator
   *
   * @param null|int $denominator
   *
   * @return static
   */
  public static function create(
    null|int|float|string|Rational $integralPartOrAny = null,
    ?int $numerator = null,
    ?int $denominator = null,
  ): static {
    if (!is_int($integralPartOrAny)) {
      if ($numerator !== null && $denominator !== null) {
        throw new InvalidArgumentException(
          'Too many arguments: only 1 is expected: "'
          . implode('", "', [$integralPartOrAny, $numerator, $denominator])
          . '".'
        );
      }
      if (is_float($integralPartOrAny)) {
        return static::fromFloat($integralPartOrAny);
      } elseif (!empty($integralPartOrAny) && is_string($integralPartOrAny)) {
        return str_contains($integralPartOrAny, '/')
          ? static::fromFractionString($integralPartOrAny)
          : static::fromDecimal($integralPartOrAny);
      } elseif ($integralPartOrAny instanceof Rational) {
        return static::fromRational($integralPartOrAny);
      } elseif (empty($integralPartOrAny)) {
        return static::zero();
      }
    }
    return new static($integralPartOrAny, $numerator ?? 0, $denominator ?? 1);
  }

  /**
   * Generate a new instance from a given base-class instance.
   *
   * @param Rational $rational Construct an instance given a base-class instance.
   *
   * @return static
   */
  public static function fromRational(Rational $rational): static
  {
    return new static($rational->whole, $rational->numerator, $rational->denominator, true);
  }

  /**
   * Round "half away from zero".
   *
   * @param int $precision Number of decimal places after the comma.
   *
   * @return static A new instance modeling the rounded number.
   */
  public function round(int $precision = 0): static
  {
    $rollIn = pow(10, $precision + 1);
    $roundInc = ($this->getWholePart() + $this->getNumerator() < 0) ? -5 : 5;
    $integralPart = $this->getWholePart();
    // this should in principle reduce the chance of overflow ...
    $fractional = $this->sub($integralPart);
    return new static($integralPart, intdiv($fractional->mul($rollIn)->whole + $roundInc, 10), $rollIn / 10);
  }

  /**
   * @return -1 for negative numbers, +1 for positive numbers, 0 for 0.
   */
  public function sign():int
  {
    if ($this->getWholePart() < 0 || $this->getNumerator() < 0) {
      return -1;
    } elseif ($this->getWholePart() == 0 && $this->getNumerator() == 0) {
      return 0;
    }
    return 1;
  }

  /**
   * Return a correctly rounded floating point string with the given number of
   * fractional digits. Intentionally the naming of the arguments $scale and
   * $precision corresponds to the Doctrine ORM "decimal"-type parameters.
   *
   * @param int $scale Number of fractional digits to produce. If the rational
   * number cannot be exactly represented by the given number of digits then
   * the result is rounded "5 away from zero".
   *
   * @param int $precision Total number of decimal digits. If <= 0 then there
   * is no limit on the number of digits. If positive and the rational number
   * does not fit into specified number of digits, an OutOfBounds exception is
   * thrown.
   *
   * @return string
   *
   * @throws OutOfBoundsException
   */
  public function toDecimal(int $scale = -1, int $precision = 0):string
  {
    $sign = $this->sign();
    $abs = $this->abs();
    if ($scale === -1) {
      $result = $abs->whole;
      $fractionalLimit = static::DECIMAL_DIGITS_MAX - max($abs->numerator > 1 ? strlen($abs->numerator) : 0, ($result == 0 ? 0 : strlen($result)));
      $abs = $abs->round($fractionalLimit);
      $abs->subEq($result);
      $fractionalPart = '';
      while ($abs->numerator != 0 && strlen($fractionalPart) < $fractionalLimit) {
        $abs->mulEq(10);
        $digit = $abs->whole;
        $abs->subEq($digit);
        $fractionalPart .= $digit;
      }
      $result .= '.' . $fractionalPart;
      return $sign < 0 ? '-' . $result : $result;
    }
    $rollIn = pow(10, $scale + 1);
    $fixedPoint = str_pad(intdiv($abs->mul($rollIn)->whole + 5, 10), $scale + 1, '0', STR_PAD_LEFT);
    $integralPart = substr($fixedPoint, 0, -$scale);
    $fractionalPart = substr($fixedPoint, -$scale);
    $result = $integralPart . '.' . $fractionalPart;
    if ($precision > 0 && (strlen(ltrim($integralPart, '0')) + strlen($fractionalPart)) > $precision) {
      $bound = str_pad('', $precision - $scale, '9') . '.' . str_pad('', $scale, '9');
      throw new OutOfBoundsException(
        'The rational number ' . (string)$this . ' (' . $this->toFloat() . ') does not fit into the range [-' . $bound . ', ' . $bound . '].'
      );
    }
    return $sign < 0 ? '-' . $result : $result;
  }

  /**
   * Initialize an instance from a "vanilla" decimal string. Only supported
   * formats are (optional in square brackets):
   *
   * [-][D1...DN][0][.][F1....FM]
   *
   * e.g. .1234, -.1234, 0.1234, -0.1234, 1234.5678, -1234.5678, 1234.
   *
   * Read. "scientific" notation like 1e-6 is not supported.
   *
   * @param string|int $decimal
   *
   * @return static
   */
  public static function fromDecimal(string|int $decimal): static
  {
    if (is_int($decimal)) {
      return new static($decimal, 0, 1);
    }
    $matches = [];
    if (!preg_match_all('/^(-)?([1-9]\d*|0?)(?:\.(\d*))?$/', $decimal, $matches)) {
      throw new InvalidArgumentException('Unable to parse input string "' . $decimal . '".');
    }
    $sign = $matches[1][0] == '-' ? -1 : 1;
    $integralPart = empty($matches[2]) ? 0 : (int)$matches[2][0];
    $numerator = empty($matches[3]) ? 0 : (int)$matches[3][0];
    $denominator = pow(10, strlen($matches[3][0] ?? ''));
    return new static($sign * $integralPart, $sign * $numerator, $denominator);
  }

  private const SUPER_SCRIPTS = ['⁰', '¹', '²', '³', '⁴', '⁵', '⁶', '⁷', '⁸', '⁹'];
  private const SUB_SCRIPTS = ['₀', '₁', '₂', '₃', '₄', '₅', '₆', '₇', '₈', '₉'];

  /**
   * Parse a string previously generated by Rational::__toString() or
   * self::jsonSerialize() back to a number.
   *
   * @param string $input
   *
   * @return static
   */
  public static function fromFractionString(string $input): static
  {
    $originalInput = $input;
    $input = trim($input);
    $negate = false;
    if (str_starts_with($input, '-')) {
      $negate = true;
      $input = substr($input, 1);
    }
    $isInteger = filter_var($input, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    if (!$isInteger && !str_contains($input, '/')) {
      throw new InvalidArgumentException("The given string \"{$originalInput}\" does not appear to be a valid fraction.");
    }
    // replace super- and subscripts back to ordinary numbers.
    $from = array_merge(self::SUPER_SCRIPTS, self::SUB_SCRIPTS);
    $from[] = ' ';
    $to = array_merge(array_keys(self::SUPER_SCRIPTS), array_keys(self::SUB_SCRIPTS));
    $to[] = '+';
    $input = str_replace($from, $to, $input);
    // now we have at any rate the jsonSerialize() representation.
    $parts = array_filter(explode('+', $input));
    $numParts = count($parts);
    switch ($numParts) {
      case 2:
        [$whole, $fraction] = $parts;
        break;
      case 1:
        if (str_contains($parts[0], '/')) {
          $whole = 0;
          $fraction = $parts[0];
        } else {
          $whole = $parts[0];
          $fraction = '0/1';
        }
        break;
      default:
      case 0:
        throw new InvalidArgumentException("The given string \"{$originalInput}\" does not appear to be a valid fraction.");
    }
    [$numerator, $denominator] = explode('/', $fraction);
    $number = new static((int)$whole, (int)$numerator, (int)$denominator);
    return $negate ? $number->negate() : $number;
  }

  /**
   * Try to convert the given float into a RationalNumber.
   *
   * @param float $value
   *
   * @return static
   *
   * @bug
   */
  public static function fromFloat(float $value): static
  {
    $valueString = sprintf('%.16f', $value);
    // @todo: range checking
    return static::fromDecimal($valueString);
  }

  /**
   * Replace this instance by the given argument.
   *
   * @param mixed $other An instance of RationaNumber or something which can be
   * converted by RationalNumber::create() to a RationalNumber.
   *
   * @return static $this.
   */
  public function assign(mixed $other): static
  {
    $result = static::ensureRationalNumber($other);
    $this->whole = $result->whole;
    $this->numerator = $result->numerator;
    $this->denominator = $result->denominator;

    return $this;
  }

  /** {@inheritdoc} */
  public function abs(): static
  {
    return static::fromRational(parent::abs());
  }

  /**
   * Make the current number non-negative in-place.
   *
   * @return static $this.
   */
  public function absEq(): static
  {
    return $this->assign($this->abs());
  }

  /** {@inheritdoc} */
  public function inverse(): static
  {
    return static::fromRational(parent::inverse());
  }

  /**
   * Shortcut for RationalNumber::invsere().
   *
   * @return static
   */
  public function inv(): static
  {
    return $this->inverse();
  }

  /**
   * Invert the current instance in place.
   *
   * @return static $this.
   */
  public function invEq(): static
  {
    return $this->assign($this->inv());
  }

  /**
   * @return static -$this
   */
  public function negate(): static
  {
    return new static(-$this->whole, -$this->numerator, $this->denominator, skipNormalization: true);
  }

  /**
   * Shortcut for RationalNumber::negate().
   *
   * @return static
   */
  public function neg(): static
  {
    return $this->negate();
  }

  /**
   * Negate the current instance in place.
   *
   * @return static $this.
   */
  public function negEq(): static
  {
    return $this->assign($this->neg());
  }

  /** {@inheritdoc} */
  public function add($r): static
  {
    return static::fromRational(parent::add(static::ensureRationalNumber($r)));
  }

  /**
   * Add the given argument to the current instance and assign the result to
   * $this. This could be optimized if the Rational::normalize() would be
   * protected, in this case the construction of a new instance could be
   * avoided.
   *
   * @param mixed $r An instance of RationaNumber or something which can be
   * converted by RationalNumber::create() to a RationalNumber.
   *
   * @return static $this.
   */
  public function addEq(mixed $r): static
  {
    return $this->assign($this->add($r));
  }

  /** {@inheritdoc} */
  public function subtract($r): static
  {
    return static::fromRational(parent::subtract(static::ensureRationalNumber($r)));
  }

  /**
   * Shortcut for RationalNumber::subtract().
   *
   * @param mixed $r An instance of RationaNumber or something which can be
   * converted by RationalNumber::create() to a RationalNumber.
   *
   * @return static $this.
   */
  public function sub(mixed $r): static
  {
    return $this->subtract($r);
  }

  /**
   * Subtract the given argument to the current instance. This could be
   * optimized if the Rational::normalize() would be protected, in this case
   * the construction of a new instance could be avoided.
   *
   * @param mixed $r An instance of RationaNumber or something which can be
   * converted by RationalNumber::create() to a RationalNumber.
   *
   * @return static $this.
   */
  public function subEq(mixed $r): static
  {
    return $this->assign($this->sub($r));
  }

  /** {@inheritdoc} */
  public function multiply($r): static
  {
    return static::fromRational(parent::multiply(static::ensureRationalNumber($r)));
  }

  /**
   * Shortcut for RationalNumber::multiply().
   *
   * @param mixed $r An instance of RationaNumber or something which can be
   * converted by RationalNumber::create() to a RationalNumber.
   *
   * @return static $this.
   */
  public function mul(mixed $r): static
  {
    return $this->multiply($r);
  }

  /**
   * Multiply the given argument with the current instance and assign the
   * result to $this. This could be optimized if the Rational::normalize()
   * would be protected, in this case the construction of a new instance could
   * be avoided.
   *
   * @param mixed $r An instance of RationaNumber or something which can be
   * converted by RationalNumber::create() to a RationalNumber.
   *
   * @return static $this.
   */
  public function mulEq(mixed $r): static
  {
    return $this->assign($this->mul($r));
  }

  /** {@inheritdoc} */
  public function divide($r): static
  {
    return static::fromRational(parent::divide(static::ensureRationalNumber($r)));
  }

  /**
   * Shortcut for RationalNumber::divide().
   *
   * @param mixed $r An instance of RationaNumber or something which can be
   * converted by RationalNumber::create() to a RationalNumber.
   *
   * @return static $this.
   */
  public function div(mixed $r): static
  {
    return $this->divide($r);
  }

  /**
   * Divide the current instance by the given argument and assign the
   * result to $this. This could be optimized if the Rational::normalize()
   * would be protected, in this case the construction of a new instance could
   * be avoided.
   *
   * @param mixed $r An instance of RationaNumber or something which can be
   * converted by RationalNumber::create() to a RationalNumber.
   *
   * @return static $this.
   */
  public function divEq(mixed $r): static
  {
    return $this->assign($this->div($r));
  }

  /** {@inheritdoc} */
  public function pow(int $exponent): static
  {
    return static::fromRational(parent::pow($exponent));
  }

  /** {@inheritdoc} */
  public static function createZeroValue(): static
  {
    return static::fromRational(parent::createZeroValue());
  }

  /**
   * @return static Normalized representation of zero.
   */
  public static function zero(): static
  {
    return new static(0);
  }

  /** {@inheritdoc} */
  public function equals(mixed $number):bool
  {
    return parent::equals(static::ensureRationalNumber($number));
  }

  /**
   * \true iff $this == $other
   *
   * @param mixed $other
   *
   * @return bool
   */
  public function eq(mixed $other):bool
  {
    return $this->equals($other);
  }

  /**
   * \true iff $this > $other
   *
   * @param mixed $other
   *
   * @return bool
   */
  public function gt(mixed $other):bool
  {
    return $this->subtract($other)->sign() > 0;
  }

  /**
   * \true iff $this >= $other
   *
   * @param mixed $other
   *
   * @return bool
   */
  public function ge(mixed $other):bool
  {
    $sign = $this->subtract($other)->sign();
    return $sign > 0 || $sign == 0;
  }

  /**
   * \true iff $this < $other
   *
   * @param mixed $other
   *
   * @return bool
   */
  public function lt(mixed $other):bool
  {
    return $this->subtract($other)->sign() < 0;
  }

  /**
   * \true iff $this <= $other
   *
   * @param mixed $other
   *
   * @return bool
   */
  public function le(mixed $other):bool
  {
    $sign = $this->subtract($other)->sign();
    return $sign < 0 || $sign == 0;
  }

  /**
   * Convenience, return the minimum using exact arithmetic.
   *
   * @param mixed $a
   *
   * @param mixed $b
   *
   * @return static
   */
  public static function min(mixed $a, mixed $b): RationalNumber
  {
    $aNumber = static::ensureRationalNumber($a);
    return $aNumber->le($b) ? $aNumber : static::ensureRationalNumber($b);
  }

  /**
   * Convenience, return the maximum using exact arithmetic.
   *
   * @param mixed $a
   *
   * @param mixed $b
   *
   * @return static
   */
  public static function max(mixed $a, mixed $b): RationalNumber
  {
    $aNumber = static::ensureRationalNumber($a);
    $bNumber = static::ensureRationalNumber($b);
    return $aNumber->ge($bNumber) ? $aNumber : $bNumber;
  }

  /**
   * Generate an instance of RationalNumber from $other if it is not already
   * an instance of RationalNumber.
   *
   * @param mixed $other
   *
   * @return static
   */
  protected static function ensureRationalNumber(mixed $other): static
  {
    return ($other instanceof static) ? $other : static::create($other);
  }

  /**
   * Return a less fancy string repesentation (-)W+N/D without Unicode super-
   * and sub-scripts.
   *
   * @return string
   */
  public function jsonSerialize(): mixed
  {
    $sign = $this->sign() == -1 ? '-' : '';
    $parts = [];
    if ($this->whole != 0) {
      $parts[] = (string)\abs($this->whole);
    }
    if ($this->numerator != 0) {
      $parts[] = (string)\abs($this->numerator) . '/' . (string)$this->denominator;
    }
    return $sign . implode('+', $parts);
  }
}
