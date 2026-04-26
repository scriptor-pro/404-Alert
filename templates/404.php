<?php
/**
 * Template 404 personnalisé pour 404 Alert
 *
 * @package Alert404
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title><?php bloginfo( 'name' ); ?> - Page non trouvée</title>
	<style>
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}
		body {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
			color: #333;
			line-height: 1.6;
			background: #f9f9f9;
		}
		.container {
			min-height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 20px;
		}
		.content {
			max-width: 600px;
			text-align: center;
			background: white;
			padding: 60px 40px;
			border-radius: 8px;
			box-shadow: 0 2px 4px rgba(0,0,0,0.1);
		}
		.error-code {
			font-size: 120px;
			font-weight: bold;
			color: #e74c3c;
			margin-bottom: 20px;
			line-height: 1;
		}
		h1 {
			font-size: 36px;
			margin: 20px 0;
			color: #333;
		}
		p {
			font-size: 18px;
			color: #666;
			margin: 20px 0;
		}
		.alert-box {
			background: #f5f5f5;
			border-left: 4px solid #e74c3c;
			padding: 20px;
			margin: 30px 0;
			text-align: left;
			border-radius: 4px;
		}
		.alert-box p {
			margin: 10px 0;
			text-align: left;
		}
		.alert-box p:first-child {
			margin-top: 0;
			font-weight: bold;
			color: #333;
		}
		.alert-box p:last-child {
			margin-bottom: 0;
			color: #666;
			font-size: 14px;
		}
		.button {
			display: inline-block;
			background: #0073aa;
			color: white;
			padding: 12px 30px;
			text-decoration: none;
			border-radius: 4px;
			font-weight: bold;
			margin-top: 40px;
			transition: background 0.3s ease;
		}
		.button:hover {
			background: #005a87;
		}
		.footer-info {
			margin-top: 60px;
			padding-top: 30px;
			border-top: 1px solid #ddd;
			color: #999;
			font-size: 14px;
		}
		code {
			background: #f0f0f0;
			padding: 2px 6px;
			border-radius: 3px;
			word-break: break-all;
		}
	</style>
</head>
<body>
	<div class="container">
		<div class="content">
			<div class="error-code">404</div>
			<h1>Page non trouvée</h1>
			<p>Désolé, la page que tu cherches n'existe pas ou a été déplacée.</p>

			<div class="alert-box">
				<p>ℹ️ 404 Alert activé</p>
				<p>L'administrateur du site a été notifié de cette erreur 404.</p>
			</div>

			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="button">← Retour à l'accueil</a>

			<div class="footer-info">
				<p>URL demandée : <code><?php echo esc_html( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) ) ); ?></code></p>
			</div>
		</div>
	</div>
</body>
</html>
