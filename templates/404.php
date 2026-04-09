<?php
/**
 * Template 404 personnalisé pour 404 Alert
 */

get_header();
?>

<div id="primary" class="content-area" style="padding: 40px 20px; text-align: center; min-height: 400px; display: flex; align-items: center; justify-content: center;">
	<div class="page-content" style="max-width: 600px;">
		<div style="margin-bottom: 30px;">
			<div style="font-size: 120px; font-weight: bold; color: #e74c3c; margin: 0; line-height: 1;">
				404
			</div>
		</div>

		<h1 style="font-size: 36px; margin: 20px 0; color: #333;">
			Page non trouvée
		</h1>

		<p style="font-size: 18px; color: #666; margin: 20px 0 30px;">
			Désolé, la page que tu cherches n'existe pas ou a été déplacée.
		</p>

		<div style="background: #f5f5f5; border-left: 4px solid #e74c3c; padding: 20px; margin: 30px 0; text-align: left;">
			<p style="margin: 0 0 10px; font-weight: bold; color: #333;">
				ℹ️ 404 Alert activé
			</p>
			<p style="margin: 0; color: #666; font-size: 14px;">
				L'administrateur du site a été notifié de cette erreur 404.
			</p>
		</div>

		<div style="margin-top: 40px;">
			<a href="<?php echo esc_url(home_url('/')); ?>"
			   style="display: inline-block; background: #0073aa; color: white; padding: 12px 30px; text-decoration: none; border-radius: 4px; font-weight: bold;">
				← Retour à l'accueil
			</a>
		</div>

		<div style="margin-top: 60px; padding-top: 30px; border-top: 1px solid #ddd;">
			<p style="color: #999; font-size: 14px;">
				URL demandée : <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;"><?php echo esc_html($_SERVER['REQUEST_URI'] ?? '/'); ?></code>
			</p>
		</div>
	</div>
</div>

<?php
get_footer();
