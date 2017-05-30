<?php
App::uses('AppModel', 'Model');
/**
 * Book Model
 *
 * @property Evaluation $Evaluation
 * @property LessonCancel $LessonCancel
 * @property LessonNote $LessonNote
 * @property LessonRequest $LessonRequest
 * @property Lesson $Lesson
 * @property Member $Member
 * @property StudentMemo $StudentMemo
 */
class Book extends AppModel {

	/**
	 * Use table
	 *
	 * @var mixed False or table name
	 */
	public $useTable = 'book';



}
