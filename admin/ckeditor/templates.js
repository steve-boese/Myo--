// Register a templates definition set named "default".
CKEDITOR.addTemplates( 'default',
{
	// The name of sub folder which hold the shortcut preview images of the
	// templates.
	imagesPath : '/admin/ckeditor/image/' ,

	// The templates definitions.
	templates :
		[
			{
				title: 'Manufacturer Logos',
				image: 'logo-table.gif',
				description: '3-column table for Manufacturer Logos.',
				html:
					'<h2>' +
						'Bedpost Furniture Offers *** Collections by:' +
					'</h2>' +
					'<table id="manuf_logos">' +
						'<tr>' +
						'<td>' +
						'</td>' +
						'<td>' +
						'</td>' +
						'<td>' +
						'</td>' +
						'</tr>' +
					'</table>'
			}
		]
});
