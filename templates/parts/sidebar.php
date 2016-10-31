<?php
defined( 'ABSPATH' ) or die();
?>
<div class="sidebar">
    <div id="index">
        <h4><?php $this->e( 'Index' ); ?></h4>
        <ol>
        </ol>
        <p class="forum-link">
            <?php printf(
                $this->_( 'Have some questions? Go to our support site <a href="%s" target="_blank">gianism.info</a>!' ),
                gianism_utm_link( 'https://gianism.info/', [
                    'utm_medium' => 'sidebar',
                ] )
            ); ?>
        </p>

        <p class="github-link">
            <?php printf( $this->_( 'This plugin\'s is hosted on <a href="%s">Github</a>. Pull requests are welcomed.' ), 'https://github.com/fumikito/Gianism' ); ?>
        </p>

        <div class="fb-page" data-href="https://www.facebook.com/gianism.info" data-small-header="true"
             data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="true"
             data-show-posts="false">
            <div class="fb-xfbml-parse-ignore">
                <blockquote cite="https://www.facebook.com/gianism.info"><a
                        href="https://www.facebook.com/gianism.info">Gianism</a></blockquote>
            </div>
        </div>
        <p class="social-link">
            <a href="https://twitter.com/intent/tweet?screen_name=wpGianism" class="twitter-mention-button"
               data-lang="ja" data-related="takahashifumiki">Tweet to @wpGianism</a>
            <script>!function (d, s, id) {
                    var js, fjs = d.getElementsByTagName(s)[0];
                    if (!d.getElementById(id)) {
                        js = d.createElement(s);
                        js.id = id;
                        js.src = "//platform.twitter.com/widgets.js";
                        fjs.parentNode.insertBefore(js, fjs);
                    }
                }(document, "script", "twitter-wjs");</script>
        </p>
    </div>
</div>
