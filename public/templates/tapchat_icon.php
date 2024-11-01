<?php wp_head(); ?>
<div id="app" class="fixed bottom-6 right-6">
    <div class="h-32 w-32 animate-bounce" @click="init_call_request">
        <img src="<?php echo esc_url(TAPCHAT_PLUGIN_URL.'/assets/images/public-icon.png'); ?>" alt="public-icon-alt"
            class="rounded-full cursor-pointer">
    </div>
</div>