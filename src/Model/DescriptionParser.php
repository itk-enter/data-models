<?php

namespace ItkEnter\DataModels\Model;

/**
 * Parses a top-level property's description against the SDM convention:
 * "<Property|Relationship|GeoProperty>. [Model:'…'. ]free text[ Enum:'…'.]
 * [ Units:'…'.]". Each structured marker is optional except the leading
 * NGSI type; the generators (phase 4) rely on this being followed, so it's
 * checked here rather than assumed.
 *
 * SDM's model.yaml strips only the leading "<NgsiType>. " token before
 * display — Model:'…', Enum:'…' and any trailing "Source: …" note stay in
 * the text. `text` matches that. doc/spec.md's property list additionally
 * drops the Model:'…' marker itself, showing the model URL as a separate
 * link instead — `withoutMarker()` produces that further-stripped text.
 */
final class DescriptionParser
{
    private const NGSI_TYPES = ['Property', 'Relationship', 'GeoProperty'];

    public static function parse(string $description): ParsedDescription
    {
        $ngsiType = null;
        $text = $description;
        foreach (self::NGSI_TYPES as $type) {
            $prefix = $type.'.';
            if (str_starts_with($description, $prefix)) {
                $ngsiType = $type;
                $text = ltrim(substr($description, \strlen($prefix)));
                break;
            }
        }

        $model = self::extract($description, 'Model');
        $units = self::extract($description, 'Units');
        $enumText = self::extract($description, 'Enum');
        $enum = null === $enumText ? null : array_map(trim(...), explode(',', $enumText));

        return new ParsedDescription($ngsiType, $model, $units, $enum, $text);
    }

    /**
     * `$text` with a `Key:'…'` marker (and one trailing period) removed.
     */
    public static function withoutMarker(string $text, string $key): string
    {
        $stripped = preg_replace('/\s*'.preg_quote($key, '/').":'[^']*'\\.?/", '', $text);

        return trim($stripped ?? $text);
    }

    /**
     * Whether the description contains a `Key:'…'` marker that failed to
     * match the closing quote, e.g. `Enum:'unterminated`.
     */
    public static function hasUnterminatedMarker(string $description, string $key): bool
    {
        return str_contains($description, $key.':\'') && null === self::extract($description, $key);
    }

    private static function extract(string $description, string $key): ?string
    {
        if (preg_match('/'.preg_quote($key, '/').":'([^']*)'/", $description, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
