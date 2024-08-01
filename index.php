<?php 

if (!function_exists('mona_download_image_from_url')) {

function mona_download_image_from_url($url)
{
    // Gives us access to the download_url() and wp_handle_sideload() functions
    require_once(ABSPATH . 'wp-admin/includes/file.php');

    // URL to the WordPress logo
    // $url = 'http://s.w.org/style/images/wp-header-logo.png';
    $timeout_seconds = 5;

    // Download file to temp dir
    $temp_file = download_url($url, $timeout_seconds);

    if (!is_wp_error($temp_file)) {

        // Array based on $_FILE as seen in PHP file uploads
        $file = array(
            'name'     => basename($url), // ex: wp-header-logo.png
            'type'     => 'image/png',
            'tmp_name' => $temp_file,
            'error'    => 0,
            'size'     => filesize($temp_file),
        );

        $overrides = array(
            // Tells WordPress to not look for the POST form
            // fields that would normally be present as
            // we downloaded the file from a remote server, so there
            // will be no form fields
            // Default is true
            'test_form' => false,

            // Setting this to false lets WordPress allow empty files, not recommended
            // Default is true
            'test_size' => true,
        );

        // Move the temporary file into the uploads directory
        $results = wp_handle_sideload($file, $overrides);

        if (!empty($results['error'])) {
            // Insert any error handling here

            return false;
        } else {

            $filename  = $results['file']; // Full path to the file
            $local_url = $results['url'];  // URL to the file in the uploads dir
            $type      = $results['type']; // MIME type of the file

            // Perform any actions here based in the above results

            // Prepare an array of post data for the attachment.
            $attachment = array(
                'guid'           => $local_url,
                'post_mime_type' => $type,
                'post_title'     => preg_replace('/\.[^.]+$/', '', basename($filename)),
                'post_content'   => '',
                'post_status'    => 'inherit'
            );

            // create a file in the upload folder
            $upload = wp_upload_bits(basename($filename), null,  file_get_contents($filename));

            // Insert the attachment.
            $attach_id = wp_insert_attachment($attachment, $upload['file']);

            // Connect the desired file, if it is not already connected
            // wp_generate_attachment_metadata() depends on this file.
            require_once(ABSPATH . 'wp-admin/includes/image.php');


            // Create metadata for the attachment and update the post in the database.
            $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
            wp_update_attachment_metadata($attach_id, $attach_data);

            return $attach_id;
        }

        return false;
    }

    return false;
}
}