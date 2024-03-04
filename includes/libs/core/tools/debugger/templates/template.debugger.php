<?php if ($this->get('is_error')): ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <base href="<?php echo $this->get('server_url'); ?>"/>
		<title>Une erreur est apparue !</title>
		<script type="text/javascript" src="<?php echo $this->get('dir_to_components')?>/debugger/Debugger.js"></script>
        <script type="text/javascript">Debugger.error = true;</script>
		<style type="text/css"><!--@import URL("<?php echo $this->get('dir_to_components')?>/debugger/Debugger.css");--></style>
	</head>
	<body>
    <?php
endif;
    ?>
		<div id="debug"<?php if ($this->get('open')||$this->get('is_error')):?> class="fullscreen"<?php endif; ?>>
            <div class="debug_bar">
                <div class="debug_global">
                    <span class="debug_time"><?php echo $this->get('timeToGenerate');?></span>
                    <span class="debug_memory"><?php echo $this->get('memUsage');?></span>
                </div>
                <div class="debug_control">
                    <a class="debug_fullscreen"></a><a class="debug_toggle"></a><a class="debug_close"></a>
                </div>
            </div>
			<div class="debug_buttons">
				<div rel="trace" class="messages">
					<span>&nbsp;</span>Traces&nbsp; <span class="count">(<?php echo $this->get('count.trace'); ?>)</span>
				</div>
				<div rel="notice" class="messages">
					<span>&nbsp;</span>Notices <span class="count">(<?php echo $this->get('count.notice'); ?>)</span>
				</div>
				<div rel="warning" class="messages">
					<span>&nbsp;</span>Warnings <span class="count">(<?php echo $this->get('count.warning'); ?>)</span>
				</div>
				<div rel="error" class="messages">
					<span>&nbsp;</span>Erreurs & Exceptions <span class="count">(<?php echo $this->get('count.error'); ?>)</span>
				</div>
				<div rel="query" class="messages">
					<span>&nbsp;</span>Requ&ecirc;tes SQL <span class="count">(<?php echo $this->get('count.query'); ?>)</span>
				</div>
                <div rel="opcache" class="vars disabled">
                    opcache <span class="count">(<?php echo $this->get('count.opcache'); ?>)</span>
                </div>
				<div rel="cookie" class="vars disabled">
					cookie <span class="count">(<?php echo $this->get('count.cookie'); ?>)</span>
				</div>
				<div rel="session" class="vars disabled">
					session <span class="count">(<?php echo $this->get('count.session'); ?>)</span>
				</div>
				<div rel="post" class="vars disabled">
					post <span class="count">(<?php echo $this->get('count.post'); ?>)</span>
				</div>
				<div rel="get" class="vars">
					get <span class="count">(<?php echo $this->get('count.get'); ?>)</span>
				</div>
			</div>
			<div class="debug_content">
				<div class="debug_console">
					<table class="console" cellpadding="0" cellspacing="0">
						<?php echo $this->get('console'); ?>
					</table>
				</div>
				<div class="debug_vars">
					<pre rel="get"><?php echo $this->get('vars.get'); ?></pre>
					<pre rel="post" style="display:none;"><?php echo $this->get('vars.post'); ?></pre>
					<pre rel="session" style="display:none;"><?php echo $this->get('vars.session'); ?></pre>
					<pre rel="cookie" style="display:none;"><?php echo $this->get('vars.cookie'); ?></pre>
					<pre rel="opcache" style="display:none;"><?php echo $this->get('vars.opcache'); ?></pre>
				</div>
			</div>
		</div>
<?php if ($this->get('is_error')):?>
	</body>
</html>
<?php endif; ?>

