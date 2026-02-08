<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Filter;

class RadiusConfigurationParser
{
    /**
     * Parst den Radius-Konfigurationstext in ein strukturiertes Array.
     * Format: Eine Zeile pro Eintrag, "wert=Label" (z.B. "10=10 km")
     * Wert ist numerisch (Kilometer).
     *
     * @param string $configuration Mehrzeiliger Text im Format "wert=Label"
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
     * Formatiert ein Array von Radius-Einträgen zurück in das Textformat.
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
