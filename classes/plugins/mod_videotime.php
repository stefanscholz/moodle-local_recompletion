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
 * videotime handler event.
 *
 * @package     local_recompletion
 * @copyright   2025 Stefan Scholz, bdecent GmbH <sts@bdecent.de>
 * @copyright   based on code by Dan Marsden, Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_recompletion\plugins;

use lang_string;

/**
 * videotime handler event.
 *
 * @package     local_recompletion
 * @copyright   2025 Stefan Scholz, bdecent GmbH <sts@bdecent.de>
 * @copyright   based on code by Dan Marsden, Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_videotime {
    /**
     * Add params to form.
     *
     * @param moodleform $mform
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function editingform($mform): void {
        if (!self::installed()) {
            return;
        }
        $config = get_config('local_recompletion');

        $cba = array();
        $cba[] = $mform->createElement('radio', 'videotime', '',
                get_string('donothing', 'local_recompletion'), LOCAL_RECOMPLETION_NOTHING);
        $cba[] = $mform->createElement('radio', 'videotime', '',
                get_string('videotimeresetsessions', 'local_recompletion'), LOCAL_RECOMPLETION_DELETE);

        $mform->addGroup($cba, 'videotime', get_string('videotimesessions', 'local_recompletion'), array(' '), false);
        $mform->addHelpButton('videotime', 'videotimesessions', 'local_recompletion');
        $mform->setDefault('videotime', $config->videotime);
    }

    /**
     * Add sitelevel settings for this plugin.
     *
     * @param admin_settingpage $settings
     */
    public static function settings($settings) {
        if (!self::installed()) {
            return;
        }
        $choices = array(LOCAL_RECOMPLETION_NOTHING => get_string('donothing', 'local_recompletion'),
                LOCAL_RECOMPLETION_DELETE => get_string('videotimeresetsessions', 'local_recompletion'));
        $settings->add(new \admin_setting_configselect('local_recompletion/videotime',
                new lang_string('videotimesessions', 'local_recompletion'),
                new lang_string('videotimesessions_help', 'local_recompletion'), LOCAL_RECOMPLETION_NOTHING, $choices));
    }

/**
     * Reset Video Time sessions.
     *
     * @param int|string|\stdClass $user   User id or user record.
     * @param \stdClass            $course Course record.
     * @param \stdClass            $config Recompletion config.
     * @return void
     */
    public static function reset($user, \stdClass $course, \stdClass $config): void {
        global $DB;
    
        if (!self::installed()) {
            return;
        }
        if (empty($config->videotime)) {
            return;
        }
    
        // Normalize $user into an integer id (supports id, string id, or user object).
        if (is_object($user)) {
            $userid = (int)($user->id ?? 0);
        } else {
            $userid = (int)$user;
        }
        if ($userid <= 0) {
            // Nothing we can safely do.
            return;
        }
    
        if ((int)$config->videotime === LOCAL_RECOMPLETION_DELETE) {
            // module_id in the session table stores CMIDs.
            $select = 'user_id = :userid
                       AND module_id IN (
                            SELECT cm.id
                              FROM {course_modules} cm
                              JOIN {modules} m ON m.id = cm.module
                             WHERE cm.course = :courseid
                               AND m.name = :modname
                       )';
            $params = [
                'userid'   => $userid,
                'courseid' => (int)$course->id,
                'modname'  => 'videotime',
            ];
            $DB->delete_records_select('videotimeplugin_pro_session', $select, $params);
        }
    }

    /**
     * Helper function to check if Video Time Pro is installed.
     * @return bool
     */
    public static function installed() {
        global $CFG;
        if (!file_exists($CFG->dirroot.'/mod/videotime/plugin/pro/version.php')) {
            return false;
        }
        return true;
    }
}
