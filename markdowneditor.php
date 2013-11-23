<?php
require_once( 'Spyc.php' );

define( 'TEMP_DIR', 'temp' );

function parseMarkdownFile( $contents ) {
	$fieldsStart = stripos( $contents, '---', 0 ) + 3;
	$fieldsEnd = stripos( $contents, '---', $fieldsStart ) - 6;
	$fieldsStr = trim( substr( $contents, $fieldsStart, $fieldsStart + $fieldsEnd ) );
	$fields = Spyc::YAMLLoad( $fieldsStr );
	
	$contents = trim( substr( $contents, $fieldsEnd + 9 ) );
	
	$response[ 'fields' ] = $fields;
	$response[ 'markdown' ] = $contents;
	
	return $response;
}

function generateMarkdownContents( $fields, $markdown ) {
	$contents = "---\n";
	
	foreach( $fields as $name => $value ) {
		if( is_array( $value ) ) {
			$contents .= $name . ": \n";
			foreach( $value as $val ) {
				$contents .= '  - ' . $val . "\n";
			}
		}
		else {
			$contents .= $name . ': ' . $value . "\n";
		}
	}
	
	$contents .= "---\n\n";
	$contents .= $markdown;
	
	return $contents;
}

if( isset( $_POST[ 'action' ] ) ) {
	switch( $_POST[ 'action' ] ) {
		case 'get_markdown': {
			if( isset( $_POST[ 'file' ] ) ) {
				$contents = file_get_contents( $_POST[ 'file' ] );
				$response = parseMarkdownFile( $contents );
				echo( json_encode( $response ) );
			}
			else {
				echo( 'Missing file url' );
			}
			exit();
		}
		case 'save_draft': {
			$response = array(
				'success' => true,
				'message' => '',
				'url' => 'http://www.google.com'
			);
			
			$md = generateMarkdownContents( $_POST[ 'fields' ], $_POST[ 'markdown' ] );
			$success = @file_put_contents( TEMP_DIR . '/' . $_POST[ 'file_name' ], $md );
			if( $success !== false ) {
				// TODO: Copy the file to dropbox
				
			}
			else {
				$response[ 'success' ] = false;
				$response[ 'message' ] = 'Error: Could not write to temp directory.';
			}
			
			echo( json_encode( $response ) );
			exit();
		}
		case 'publish': {
			$response = array(
				'success' => true,
				'message' => '',
				'url' => 'http://www.google.com'
			);
			
			$md = generateMarkdownContents( $_POST[ 'fields' ], $_POST[ 'markdown' ] );
			$success = @file_put_contents( TEMP_DIR . '/' . $_POST[ 'file_name' ], $md );
			if( $success !== false ) {
				// TODO: Copy the file to dropbox
				
			}
			else {
				$response[ 'success' ] = false;
				$response[ 'message' ] = 'Error: Could not write to temp directory.';
			}
			
			echo( json_encode( $response ) );
			exit();
		}
	}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

<title>Markdown Editor</title>

<link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="css/style.css">

</head>
<body>

<div class="container">
	<div class="left-container">
		<div class="top-buttons">
			<button id="selectFileBtn" type="button" class="btn btn-primary">Select File</button>
		</div>
		<div class="bottom-buttons">
			<label>File name</label>
			<input id="fileName" type="text" value="" /><br/><br/>
			<button id="saveDraftBtn" type="button" class="btn btn-warning">Draft</button>
			<button id="publishBtn" type="button" class="btn btn-success">Publish</button>
		</div>
		<div id="message" class="message"></div>
	</div>
	<div class="right-container">
		<table id="fieldsTable">
			<tr>
				<td>
					<label>Title</label>
				</td>
				<td>
					<input id="title" name="title" type="text" value="" />
				</td>
				<td>
					<label>Tags</label>
				</td>
				<td>
					<input id="tags" name="tags" type="text" value="" />
				</td>
			</tr>
			<tr>
				<td>
					<label>Author</label>
				</td>
				<td>
					<input id="author" name="author" type="text" value="" />
				</td>
				<td>
					<label>Categories</label>
				</td>
				<td>
					<input id="categories" name="categories" type="text" value="" />
				</td>
			</tr>
			<tr>
				<td>
					<label>Date</label>
				</td>
				<td>
					<input id="date" name="date" type="text" value="" />
				</td>
				<td>
					<label>Layout</label>
				</td>
				<td>
					<input id="layout" name="layout" type="text" value="" />
				</td>
			</tr>
			<tr>
				<td>
					<label>Slug</label>
				</td>
				<td>
					<input id="slug" name="slug" type="text" value="" />
				</td>
				<td></td>
				<td></td>
			</tr>
			<!-- 
			<tr>
				<td>
					<label>Custom</label>
				</td>
				<td>
					<input type="text" value="" />
				</td>
				<td>
					<label>Field</label>
				</td>
				<td>
					<input type="text" value="" />
				</td>
			</tr>
			 -->
		</table>
		<br/>

		<textarea id="markdown" name="markdown" class="editor"></textarea>
	</div>
</div>

<script src="http://code.jquery.com/jquery-1.10.1.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
<script type="text/javascript" src="https://www.dropbox.com/static/api/1/dropins.js" id="dropboxjs" data-app-key=""></script>
<script>
$(document).ready(function() {

	$('input').val('');
	
	// TODO: Get this from the server
	var statiq = {
		dbApiKey: 'ptfbup4vik8jhar'
	};

	$('#dropboxjs').attr('data-app-key', statiq.dbApiKey);
	
	$('#selectFileBtn').click(function() {
		var options = {
			linkType: 'direct',
			multiselect: false,
			extensions: ['.md'],
			success: function(file) {
				console.log(file[0]);
				
				$('input').val('');

				$('#fileName').val(file[0].name);
				
				$('.editor').text('Loading...');
				var data = {
					action: 'get_markdown',
					file: file[0].link
				};
				$.post('markdowneditor.php', data, function(response) {
					console.log(response);

					$('#layout').val(response.fields.layout);
					$('#title').val(response.fields.title);
					$('#date').val(response.fields.date);
					$('#author').val(response.fields.author);
					$('#slug').val(response.fields.slug);

					var categories = '';
					$.each(response.fields.categories, function(index, value) {
						categories += value + ' ';
					});
					$('#categories').val(categories.trim());

					var tags = '';
					$.each(response.fields.tags, function(index, value) {
						tags += value + ' ';
					});
					$('#tags').val(tags.trim());
					
					$('#markdown').text(response.markdown);
				}, 'json');
			}
		};
		Dropbox.choose(options);
	});

	var getFields = function() {
		var fields = {
			layout: $('#layout').val(),
			title: $('#title').val(),
			date: $('#date').val(),
			author: $('#author').val(),
			slug: $('#slug').val(),
			categories: $('#categories').val().split(' '),
			tags: $('#tags').val().split(' ')
		}

		return fields;
	};

	$('#saveDraftBtn').click(function() {
		$('#message').html('Saving draft...');
		
		var data = {
			action: 'save_draft',
			fields: getFields(),
			markdown: $('#markdown').text(),
			file_name: $('#fileName').val()
		};

		$.post('markdowneditor.php', data, function(response) {
			console.log(response);
			if(response.success) {
				$('#message').html('Draft saved. Click <a href="' + response.url + '" target="_blank">here</a> to view it.');
			}
			else {
				alert(response.message);
			}
		}, 'json');
	});

	$('#publishBtn').click(function() {
		$('#message').html('Saving draft...');
		
		var data = {
			action: 'publish',
			fields: getFields(),
			markdown: $('#markdown').text(),
			file_name: $('#fileName').val()
		};

		$.post('markdowneditor.php', data, function(response) {
			console.log(response);
			if(response.success) {
				$('#message').html('File published. Click <a href="' + response.url + '" target="_blank">here</a> to view it.');
			}
			else {
				alert(response.message);
			}
		}, 'json');
	});
});
</script>

</body>
</html>