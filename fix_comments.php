<?php

/**
 * Fix inline comments that don't end with punctuation across all PHP files in the webchangedetector plugin.
 */

// Function to recursively find all PHP files.
function find_php_files( $directory ) {
	$files    = array();
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $directory, RecursiveDirectoryIterator::SKIP_DOTS )
	);

	foreach ( $iterator as $file ) {
		if ( $file->isFile() && $file->getExtension() === 'php' ) {
			$path = $file->getPathname();

			// Skip vendor files and backup files.
			if ( strpos( $path, '/vendor/' ) !== false ||
				strpos( $path, '.backup' ) !== false ) {
				continue;
			}

			$files[] = $path;
		}
	}

	return $files;
}

// Function to fix comments in a single file.
function fix_comments_in_file( $file_path ) {
	$content          = file_get_contents( $file_path );
	$original_content = $content;

	// Fix inline comments that don't end with punctuation.
	$content = preg_replace_callback(
		'/(\s*\/\/ )([^\n\/]+)([^.!?\n])(\r?\n)/m',
		function ( $matches ) {
			// Skip lines that are phpcs:ignore directives.
			if ( strpos( $matches[2], 'phpcs:' ) !== false ) {
				return $matches[0];
			}
			// Skip lines that are already ending with punctuation.
			if ( preg_match( '/[.!?]$/', trim( $matches[2] . $matches[3] ) ) ) {
				return $matches[0];
			}
			return $matches[1] . $matches[2] . $matches[3] . '.' . $matches[4];
		},
		$content
	);

	// Only write back if content changed.
	if ( $content !== $original_content ) {
		file_put_contents( $file_path, $content );
		return true;
	}

	return false;
}

// Get current directory (plugin root).
$plugin_directory = __DIR__;

// Find all PHP files.
echo "Scanning for PHP files in webchangedetector plugin...\n";
$php_files = find_php_files( $plugin_directory );

echo 'Found ' . count( $php_files ) . " PHP files to process.\n";

$processed_count = 0;
$modified_count  = 0;

// Process each PHP file.
foreach ( $php_files as $file ) {
	$relative_path = str_replace( $plugin_directory . '/', '', $file );
	echo "Processing: $relative_path... ";

	if ( fix_comments_in_file( $file ) ) {
		echo "MODIFIED\n";
		++$modified_count;
	} else {
		echo "no changes\n";
	}

	++$processed_count;
}

echo "\nProcessing complete!\n";
echo "Files processed: $processed_count\n";
echo "Files modified: $modified_count\n";
