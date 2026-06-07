<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Reads any Excel/CSV sheet into an array of heading-keyed rows.
 *
 * Headings are normalised to snake_case by Maatwebsite's WithHeadingRow,
 * so a column titled "Monthly Fee" becomes the key `monthly_fee`.
 */
class GenericSheetImport implements ToArray, WithHeadingRow
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    /**
     * @param  array<int, array<string, mixed>>  $array
     */
    public function array(array $array): void
    {
        $this->rows = $array;
    }
}
