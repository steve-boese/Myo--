/*
Copyright (c) 2003-2010, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.editorConfig = function( config )
{
   config.filebrowserBrowseUrl = '/kfm/';

   config.height = '425px';

   config.stylesCombo_stylesSet = 'page_styles:/admin/ckeditor/stylesSet.js';

   config.contentsCss = '/site/style/enews.css';

   config.templates_files = [ '' ];
   
   config.toolbar = 'Page';

   config.toolbar_Page =
[
    ['Source'],
    ['Maximize','Print'],
    ['Cut','Copy','Paste','PasteText','PasteFromWord','-'],
    ['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
    ['Subscript','Superscript'],
    ['About'],
    '/',
    ['Underline','Strike'],
    ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
    ['Font','FontSize'],
    ['TextColor','BGColor'],
    '/',
    ['Format'],
    ['Styles'],
    ['Bold','Italic'],
    ['Link','Unlink'],
    ['Image','Table','HorizontalRule','SpecialChar'],
    ['NumberedList','BulletedList','-','Outdent','Indent','Blockquote']

];

};
