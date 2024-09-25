<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\CrossTracker\Report\Query\Advanced\OrderByBuilder\Field\Date;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\OrderBy\DuckTypedFieldOrderBy;
use Tuleap\CrossTracker\Report\Query\Advanced\OrderByBuilder\ParametrizedFromOrder;

final class DateFromOrderBuilder
{
    public function getFromOrder(DuckTypedFieldOrderBy $field, string $order): ParametrizedFromOrder
    {
        $suffix                     = spl_object_hash($field);
        $tracker_field_alias        = "TF_$suffix";
        $changeset_value_alias      = "CV_$suffix";
        $changeset_value_date_alias = "CVDate_$suffix";
        $fields_id_statement        = EasyStatement::open()->in(
            "$tracker_field_alias.id IN (?*)",
            $field->field_ids
        );

        $from = <<<EOSQL
        LEFT JOIN tracker_field AS $tracker_field_alias
            ON (tracker.id = $tracker_field_alias.tracker_id AND $fields_id_statement)
        LEFT JOIN tracker_changeset_value AS $changeset_value_alias
            ON ($tracker_field_alias.id = $changeset_value_alias.field_id AND changeset.id = $changeset_value_alias.changeset_id)
        LEFT JOIN tracker_changeset_value_date AS $changeset_value_date_alias
            ON $changeset_value_date_alias.changeset_value_id = $changeset_value_alias.id
        EOSQL;

        return new ParametrizedFromOrder($from, $fields_id_statement->values(), "$changeset_value_date_alias.value $order");
    }
}
