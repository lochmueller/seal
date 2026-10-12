<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Filter;

class RadiusConfigurationParser
{
    /**
     * Parses the radius configuration text into a structured array.
     * Format: One entry per line, "value=Label" (e.g. "10=10 km").
     * The value is numeric (kilometers).
     *
     * @param string $configuration Multiline text in the format "value=Label"
     * @return array<int, array{value: int, label: string}>
     */
    public function parse(string $configuration): array
    {
        if (trim($configuration) === '') {
            return [];
        }

        $lines = preg_split('/\r?\n/', $configuration);
        if ($lines === false) {
            return [];
        }

        $radii = [];
        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            if ($trimmedLine === '') {
                continue;
            }

            $equalsPosition = strpos($trimmedLine, '=');
            if ($equalsPosition === false) {
                $value = $trimmedLine;
                $label = $trimmedLine;
            } else {
                $value = trim(substr($trimmedLine, 0, $equalsPosition));
                $label = trim(substr($trimmedLine, $equalsPosition + 1));
            }

            if ($value === '' || !is_numeric($value)) {
                continue;
            }

            $radii[] = [
                'value' => (int) $value,
                'label' => $label,
            ];
        }

        return $radii;
    }

    /**
     * Formats an array of radius entries back into the text format.
     *
     * @param array<int, array{value: int, label: string}> $radii
     * @return string
     */
    public function format(array $radii): string
    {
        $lines = [];
        foreach ($radii as $radius) {
            $value = (string) $radius['value'];
            $label = $radius['label'];

            if ($value === $label) {
                $lines[] = $value;
            } else {
                $lines[] = $value . '=' . $label;
            }
        }

        return implode("\n", $lines);
    }
}
