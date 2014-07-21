<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Version info
 *
 * @package    report
 * @subpackage saas-export
 * @copyright  2014 Caio Doneda and Daniel Neis
 */

$ADMIN->add('reports', new admin_externalpage('report_saas_export', get_string('pluginname', 'report_saas_export'),
            new moodle_url('/report/saas_export/index.php'), 'report/saas_export:view'));
