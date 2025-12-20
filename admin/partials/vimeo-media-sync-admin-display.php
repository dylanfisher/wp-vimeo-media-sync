<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://https://www.dylanfisher.com/
 * @since      1.0.0
 *
 * @package    Vimeo_Media_Sync
 * @subpackage Vimeo_Media_Sync/admin/partials
 */
?>

<div class="wrap">
	<h1><?php echo esc_html__( 'Vimeo Media Sync Dashboard', 'vimeo-media-sync' ); ?></h1>
	<p><?php echo esc_html__( 'Manage Vimeo sync status, background uploads, and playback settings for this site.', 'vimeo-media-sync' ); ?></p>

	<h2 class="title"><?php echo esc_html__( 'Configuration', 'vimeo-media-sync' ); ?></h2>
	<p>
		<?php echo esc_html__( 'Set the Vimeo personal access token via wp-config.php or an environment variable.', 'vimeo-media-sync' ); ?>
	</p>
	<pre><code><?php echo esc_html( "define( 'VIMEO_MEDIA_SYNC_ACCESS_TOKEN', 'your_token' );" ); ?></code></pre>
	<pre><code><?php echo esc_html( 'export VIMEO_MEDIA_SYNC_ACCESS_TOKEN=your_token' ); ?></code></pre>
	<p>
		<?php
		echo esc_html__(
			'Required scopes: public, private, create, edit, delete, upload, stats, video files.',
			'vimeo-media-sync'
		);
		?>
	</p>
	<p>
		<?php
		$status = ( '' !== $this->get_access_token() ) ? __( '✅ Detected', 'vimeo-media-sync' ) : __( '⛔️ Missing', 'vimeo-media-sync' );
		printf(
			'%s: %s',
			esc_html__( 'Token status', 'vimeo-media-sync' ),
			esc_html( $status )
		);
		?>
	</p>

	<h2 class="title"><?php echo esc_html__( 'Quick Checks', 'vimeo-media-sync' ); ?></h2>
	<ul>
		<li><?php echo esc_html__( 'Confirm Vimeo credentials are configured in your environment.', 'vimeo-media-sync' ); ?></li>
		<li><?php echo esc_html__( 'Verify recent uploads appear in Vimeo and on public pages.', 'vimeo-media-sync' ); ?></li>
		<li><?php echo esc_html__( 'Review admin and public logs for sync errors.', 'vimeo-media-sync' ); ?></li>
	</ul>

	<h2 class="title"><?php echo esc_html__( 'Helpful Links', 'vimeo-media-sync' ); ?></h2>
	<ul>
		<li>
			<a href="<?php echo esc_url( admin_url( 'upload.php' ) ); ?>">
				<?php echo esc_html__( 'Media Library', 'vimeo-media-sync' ); ?>
			</a>
		</li>
		<li>
			<a href="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>">
				<?php echo esc_html__( 'WordPress Settings', 'vimeo-media-sync' ); ?>
			</a>
		</li>
	</ul>
</div>
