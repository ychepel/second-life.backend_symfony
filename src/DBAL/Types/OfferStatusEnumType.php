<?php

namespace App\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use App\Enum\OfferStatus;
use Doctrine\DBAL\Types\Type;

class OfferStatusEnumType extends Type
{
    public const TYPE_NAME = 'offer_status_enum';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?OfferStatus
    {
        if ($value === null) {
            return null;
        }

        $enum = OfferStatus::tryFrom((int)$value);

        if (!$enum) {
            throw new \InvalidArgumentException("Invalid offer status value '{$value}' found.");
        }

        return $enum;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?int
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof OfferStatus) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid value type: expected "%s", got "%s"',
                OfferStatus::class,
                get_debug_type($value)
            ));
        }

        return $value->value;
    }

    public function getName(): string
    {
        return self::TYPE_NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getIntegerTypeDeclarationSQL($column);
    }
}