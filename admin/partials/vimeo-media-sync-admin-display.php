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
			'Required scopes: public, private, create, edit, delete, interact, upload, stats, video files.',
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
	<form method="post" action="options.php">
		<?php
		settings_fields( 'vimeo-media-sync' );
		do_settings_sections( 'vimeo-media-sync' );
		submit_button( __( 'Save Settings', 'vimeo-media-sync' ), 'secondary' );
		?>
	</form>

	<h2 class="title"><?php echo esc_html__( 'Sync Status', 'vimeo-media-sync' ); ?></h2>
	<?php
	$missing_attachments = $this->get_missing_vimeo_attachments( 10 );
	if ( isset( $_GET['synced'] ) ) :
		?>
		<p>
			<?php
			printf(
				esc_html__( 'Triggered sync for %d attachment(s).', 'vimeo-media-sync' ),
				(int) $_GET['synced']
			);
			?>
		</p>
	<?php endif; ?>
	<?php if ( empty( $missing_attachments ) ) : ?>
		<p><?php echo esc_html__( 'All video attachments are synced.', 'vimeo-media-sync' ); ?></p>
	<?php else : ?>
		<table class="widefat striped vimeo-media-sync-table">
			<thead>
				<tr>
					<th><?php echo esc_html__( 'Video', 'vimeo-media-sync' ); ?></th>
					<th><?php echo esc_html__( 'Date', 'vimeo-media-sync' ); ?></th>
					<th><?php echo esc_html__( 'Author', 'vimeo-media-sync' ); ?></th>
					<th><?php echo esc_html__( 'Last Error', 'vimeo-media-sync' ); ?></th>
					<th><?php echo esc_html__( 'Actions', 'vimeo-media-sync' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $missing_attachments as $attachment ) : ?>
					<?php $error = get_post_meta( $attachment->ID, '_vimeo_media_sync_error', true ); ?>
					<tr>
						<td>
							<a href="<?php echo esc_url( get_edit_post_link( $attachment->ID ) ); ?>">
								<?php echo esc_html( get_the_title( $attachment ) ); ?>
							</a>
						</td>
						<td><?php echo esc_html( get_the_date( '', $attachment ) ); ?></td>
						<td>
							<?php
							$author_id = (int) $attachment->post_author;
							$author_name = $author_id ? get_the_author_meta( 'display_name', $author_id ) : '';
							echo esc_html( $author_name );
							?>
						</td>
						<td>
							<?php if ( $error ) : ?>
								<span class="vimeo-media-sync-error" title="<?php echo esc_attr( $error ); ?>">
									<?php echo esc_html( $error ); ?>
								</span>
							<?php else : ?>
								<?php echo esc_html__( '—', 'vimeo-media-sync' ); ?>
							<?php endif; ?>
						</td>
						<td>
							<button
								type="button"
								class="button button-small vimeo-media-sync-row"
								data-post-id="<?php echo (int) $attachment->ID; ?>"
							>
								<?php echo esc_html__( 'Sync', 'vimeo-media-sync' ); ?>
							</button>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p>
			<button type="button" class="button button-small vimeo-media-sync-all">
				<?php echo esc_html__( 'Sync missing videos', 'vimeo-media-sync' ); ?>
			</button>
		</p>
	<?php endif; ?>

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
