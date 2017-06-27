;<?php exit() ?>
; SVN FILE: $Id$
;/**
; * Test App Ini Based Acl Config File
; *
; * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
; * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
; *
; *  Licensed under The MIT License
; *  Redistributions of files must retain the above copyright notice.
; *
; * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
; * @link          https://cakephp.org CakePHP(tm) Project
; * @package       Cake.Test.TestApp.Config
; * @since         CakePHP(tm) v 0.10.0.1076
; * @license       https://opensource.org/licenses/mit-license.php MIT License
; */

;-------------------------------------
;Users
;-------------------------------------

[admin]
groups = administrators
allow =
deny = ads

[paul]
groups = users
allow =
deny =

[jenny]
groups = users
allow = ads
deny = images, files

[nobody]
groups = anonymous
allow =
deny =

;-------------------------------------
;Groups
;-------------------------------------

[administrators]
deny =
allow = posts, comments, images, files, stats, ads

[users]
allow = posts, comments, images, files
deny = stats, ads

[anonymous]
allow =
deny = posts, comments, images, files, stats, ads
