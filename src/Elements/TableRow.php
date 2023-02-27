<?php
namespace TypeRocket\Pro\Elements;

use TypeRocket\Models\Model;
use TypeRocket\Utility\Data;

class TableRow
{
    protected $record;

    public function __construct($record)
    {
        $this->record = $record;
    }

    public function getID()
    {
        static $count = 0;

        if($this->record instanceof Model) {
            return $this->record->getID();
        }

        return $count++;
    }

    public function getDeepValue($dots)
    {
        if($this->record instanceof Model) {
            return $this->record->getDeepValue($dots, true);
        }

        return Data::walk($dots, $this->record);
    }

    public static function buildTableRowActions($text, $links, $record, Table $table, $data = [], $route_args = [])
    {
        $links = apply_filters('typerocket_table_row_actions', $links, $record, $table, $data, $route_args);

        $text .= "<div class=\"row-actions\">" . implode(' | ', $links) . "</div>";

        return $text;
    }
}