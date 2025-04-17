<?php
namespace Ormurin\Hull\Typification;

class TypeFactory
{
    private function __construct() {}
    private static ?array $types = null;

    public static function registerDefaultTypes(): void
    {
        $type_classes = [
            MixedType::class,
            StringType::class,
            TrimmedStringType::class,
            FloatType::class,
            AbsFloatType::class,
            IntegerType::class,
            AbsIntegerType::class,
            BooleanType::class,
            ArrayType::class,
        ];
        foreach ( $type_classes as $type_class ) {
            self::registerType(call_user_func([$type_class, 'getNames']), $type_class);
        }
    }

    public static function registerType(array|string $type_name, string $type_class): void
    {
        if ( !class_exists($type_class) ) {
            throw new \InvalidArgumentException("type class not found: $type_class");
        }
        if ( !is_a($type_class, BaseType::class, true) ) {
            throw new \InvalidArgumentException("type class has to be a class of " . BaseType::class);
        }
        if ( !is_array($type_name) ) {
            $type_name = [$type_name];
        }
        if ( !$type_name ) {
            throw new \InvalidArgumentException("type name is empty");
        }
        if ( !is_array(self::$types) ) {
            self::$types = [];
        }
        foreach ( $type_name as $name ) {
            if ( !is_string($name) ) {
                throw new \InvalidArgumentException("type name must be a string");
            }
            self::$types[$name] = $type_class;
        }
    }

    public static function unregisterType(array|string $type_name): void
    {
        if ( !is_array(self::$types) ) {
            return;
        }
        if ( !is_array($type_name) ) {
            $type_name = [$type_name];
        }
        foreach ( $type_name as $name ) {
            if ( !is_string($name) ) {
                throw new \InvalidArgumentException("type name must be a string");
            }
            unset(self::$types[$name]);
        }
    }

    public static function unregisterAllTypes(): void
    {
        self::$types = [];
    }

    public static function isTypeRegistered(string $type_name): bool
    {
        if ( self::$types === null ) {
            self::registerDefaultTypes();
        }
        return !empty(self::$types[$type_name]);
    }

    public static function make(string $type_name, mixed $raw_value, mixed $default_value = ValueCase::Default): ?BaseType
    {
        if ( !self::isTypeRegistered($type_name) ) {
            throw new \InvalidArgumentException("unknown type: $type_name");
        }
        return self::$types[$type_name]($raw_value, $default_value);
    }

    public static function value(mixed $raw_value, ?string $type = null, mixed $default_value = ValueCase::Default): mixed
    {
        if ( $type === null ) {
            return $raw_value;
        }
        $type = self::make($type, $raw_value, $default_value);
        return $type->getValue();
    }
}
