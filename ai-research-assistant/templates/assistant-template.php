<?php
/* Assistant Virtual Template */
// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

// Output the content.
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Assistant</title>
    <link rel="icon" type="image/x-icon" href="<?php echo plugins_url( 'ai-research-assistant/images/favicon.ico'); ?>">
    <?php wp_head(); ?>
</head>
<body class="aiAssistant">
    <div id="overlay">
        <div class="cv-spinner">
            <span class="spinner"></span>
        </div>
    </div>
    <div class="jd-main">
        <div class="jd-logo">
            <?php 
                $logo_url = get_option( 'ai_research_assistant_logo' );
                $home_url = home_url();
                if ( $logo_url ) {
                    echo '<a href="' . esc_url( $home_url ) . '"><img src="' . esc_url( $logo_url ) . '" alt="Chat Assistant Logo"></a>';
                } else {
                    echo '<a href="' . esc_url( $home_url ) . '"><img src="' . plugins_url( 'ai-research-assistant/images/logo.svg') .'" alt="Chat Assistant Logo"></a>';
                }
            ?>
        </div>
        <div class="container">
            <div class="jd-content">
                <?php 
                    $assis_head = esc_html( get_option( 'ai_research_assistant_heading') ); 
                    $assis_head = $assis_head ? $assis_head : "AI-curated answers to all of your questions about our company.";
                    $assis_cont = esc_html( get_option( 'ai_research_assistant_text' ) );
                    $assis_cont = $assis_cont ? $assis_cont : "Get answers to your questions with our AI-powered research assistant. This assistant is trained on our website content to make finding the information you need faster and easier.";
                ?>
                <h1 class="jd-heading"><?=$assis_head?></h1>
                <p class="jd-text"><?=$assis_cont;?></p>
            </div>

            
            <div class="jd-form-outer">
                <form class="jd-form" id="chat-form">
                    <?php
                        $placeholder_text = esc_attr( get_option( 'ai_research_assistant_placeholder_text' ) );
                        $placeholder_text = $placeholder_text ? $placeholder_text : "How can we help you?";
                    ?>
                    <input type="text" placeholder="<?php echo $placeholder_text; ?>" class="form-input" id="chat-input">
                    <div class="jd-form-button">
                        <button type="button" id="send-chat" disabled><img src="<?php echo plugins_url( 'ai-research-assistant/images/arrow-up.svg'); ?>" alt="Logo"></button>
                    </div>
                </form>
            </div>
            <div class="message-outerds">
                <div id="answerText-parent">
                    <div class="answer-message" id="answerMessage"></div>
                    <div class="copy-text-outer">
                        <div class="loaderText">
                            <p>Combing through Juris Digital’s content </p>
                        </div>
                    </div> 
                </div>
                <div id="references-text"></div>
                <hr class="jd-divider"/>
                <div class="chat-custom-footer jd-started">
                    <?php 
                        $footerText = get_option('ai_research_assistant_footer_text');
                        $footerText = $footerText ? $footerText : "Ready to talk to a human? We have those, too. Reach out anytime.";
                        $contact_us_link = get_option('ai_research_assistant_contact_us_link'); // New line
                    ?>
                    <p class="jd-started-text"><?php echo esc_html( $footerText ); ?></p>
                    <?php if ($contact_us_link): ?>
                        <a href="<?php echo esc_url($contact_us_link); ?>" class="jd-started-btn">CONTACT US</a>
                    <?php else: ?>
                        <button id="get-started-btn" class="jd-started-btn">CONTACT US</button>
                    <?php endif; ?>
                </div>
            </div>      
        </div>
    </div>
    <!-- </body> -->
    <?php wp_footer();?>

    <!-- Usersnap Code -->
    <script>
    window.onUsersnapLoad = function(api) {
        api.init();
    };
    var script = document.createElement('script');
    script.defer = 1;
    script.src = 'https://widget.usersnap.com/global/load/9dbdf42d-4aaf-4747-9c37-38f221c8914b?onload=onUsersnapLoad';
    document.getElementsByTagName('head')[0].appendChild(script);
    </script>
</body>

</html>
