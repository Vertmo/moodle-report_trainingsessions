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
 * the gradesettings service allows configuring the grades to be added to the trainingsession 
 * report for this course. 
 * Grades will be appended to the time report
 *
 * The global course final grade can be selected along with specified modules to get score from.
 *
 * @package    report_trainingsessions
 * @author     Valery Fremaux (valery.fremaux@gmail.com)
 * @version    moodle 2.x
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/report/trainingsessions/gradesettings_form.php');

$id = required_param('id', PARAM_INT); // course id
$from = required_param('from', PARAM_INT);
$to = required_param('to', PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourse');
}

$context = context_course::instance($course->id);

require_course_login($course);
require_capability('report/trainingsessions:downloadreports', $context);

$url = new moodle_url('/report/trainingsessions/index.php', array('id' => $id));
$PAGE->set_url($url);
$PAGE->set_heading(get_string('gradesettings', 'report_trainingsessions'));
$PAGE->set_title(get_string('gradesettings', 'report_trainingsessions'));
$PAGE->navbar->add(get_string('gradesettings', 'report_trainingsessions'));

$form = new TrainingsessionsGradeSettingsForm();

$renderer = $PAGE->get_renderer('report_trainingsessions');
$coursemodinfo = get_fast_modinfo($course->id);

if ($data = $form->get_data()) {
    // delete all previous recordings
    $DB->delete_records('report_trainingsessions', array('courseid' => $COURSE->id));

    // activate course grade
    if (!empty($data->coursegrade)) {
        $rec = new StdClass();
        $rec->courseid = $COURSE->id;
        $rec->moduleid = 0;
        $rec->sortorder = 0;
        $rec->label = $data->courselabel;
        $DB->insert_record('report_trainingsessions', $rec);
    }

    foreach ($data->moduleid as $ix => $moduleid) {
        if ($moduleid) {
            $rec = new StdClass();
            $rec->courseid = $COURSE->id;
            $rec->moduleid = $moduleid;
            $cminfo = $coursemodinfo->get_cm($moduleid);
            $rec->label = (empty($data->scorelabel[$ix])) ? (($cminfo->idnumber) ? $cminfo->idnumber : $cminfo->modulename.$cminfo->instance) : $data->scorelabel[$ix];
            $rec->sortorder = $ix;
            $DB->insert_record('report_trainingsessions', $rec);
        }
    }
    redirect(new moodle_url('/report/trainingsessions/gradessettings.php', array('id' => $COURSE->id, 'view' => 'gradesettings', 'from' => $from, 'to' => $to)));
}

echo $OUTPUT->header();

echo $renderer->tabs($course, 'gradesettings', $from, $to);

echo $OUTPUT->heading(get_string('scoresettings', 'report_trainingsessions'));

echo $OUTPUT->notification(get_string('scoresettingsadvice', 'report_trainingsessions'));

// Prepare form feed in.
$alldata = $DB->get_records('report_trainingsessions', array('courseid' => $COURSE->id), 'sortorder');
if ($alldata) {
    $ix = 0;
    $formdata = new StdClass();
    $formdata->from = $from;
    $formdata->to = $to;
    foreach ($alldata as $datum) {
        if ($datum->moduleid == 0) {
            $formdata->coursegrade = 1;
            $formdata->courselabel = $datum->label;
        } else {
            $formdata->moduleid[$ix] = $datum->moduleid;
            $formdata->scorelabel[$ix] = $datum->label;
            $ix++;
        }
    }
    $form->set_data($formdata);
} else {
    $form->from = $from;
    $form->to = $to;
    $form->set_data($form);
}

// display form.
$form->display();

echo $OUTPUT->footer();