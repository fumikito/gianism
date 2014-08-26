<?php

defined('ABSPATH') or die();

/** @var \Gianism\Admin $this */
/** @var \Gianism\Option $option */

/** @var \Gianism\Service\Google $google */
$google = \Gianism\Service\Google::get_instance();

?>

<div class="wrap gianism-wrap">

    <div id="fb-root"></div>
    <script>(function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s); js.id = id;
        js.src = "//connect.facebook.net/ja_JP/all.js#xfbml=1&appId=264573556888294";
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));</script>

    <h2><i class="lsf lsf-graph"></i> <?php $this->e('Gianism Google Analytics') ?> <small class="description">(<?php $this->e('Experimental') ?>)</small></h2>

    <p class="description">
        <?php $this->e('This feature is very experimental. Knowledge for Google Analytics and OAuth will be required.'); ?><br />
        <?php $this->e('If you are well experimented developer, it might be useful for making various ranking for custom post type, category ranking and so on.'); ?>
    </p>

    <br class="clear" />

    <?php include __DIR__.'/sidebar.php' ?>
    <div class="main-content">

        <?php if( !$google->is_enabled() ): ?>
            <div class="error">
                <p><?php printf($this->_('Google connect is not available. Go to <a href="%s">setting page</a> and turn it on.'), admin_url('options-general.php?page=gianism')) ?></p>
            </div>
        <?php else: ?>
            <h3><i class="lsf lsf-key"></i> <?php $this->e('Analytics Token') ?></h3>

            <?php if( $google->ga_token ): ?>
                <p class="success">
                    <i class="lsf lsf-ok"></i> <?php $this->e('O.K. You have token.') ?>
                </p>
                <code><?php echo $google->ga_token ?></code>
                <?php
                    $label = $this->_('Restore');
                ?>
            <?php else: ?>
                <p class="description">
                    <i class="lsf lsf-ban"></i> <?php $this->e('You don\'t have token. Token is required to contact with Google Analytics API.') ?>
                </p>
                <?php
                    $label = $this->_('Get');
                ?>
            <?php endif; ?>
            <p class="submit">
                <a class="button-primary" href="<?php echo $google->token_url(admin_url('tools.php?page=gianism_ga')) ?>"><?php echo $label; ?></a>
                <?php if( $google->ga_token ): ?>
                <a class="button" href="<?php echo $google->token_url(admin_url('tools.php?page=gianism_ga'), true) ?>"><?php $this->e('Delete') ?></a>
                <?php endif; ?>
            </p>

            <?php if( $google->ga_token ): ?>
            <h3><i class="lsf lsf-dashboard"></i> <?php $this->e('Account, Profile, View') ?></h3>
            <form id="ga-connection" action="<?php echo $google->token_save_url(admin_url('tools.php?page=gianism_ga')) ?>" method="post" data-endpoint="<?php echo admin_url('admin-ajax.php?action=wpg_ga_account') ?>">
                <?php wp_nonce_field($this->nonce_action('google_save-analytics'), $this->nonce_key_name) ?>
                <table class="form-table">
                    <tbody>
                    <tr>
                        <th><label for="ga-account"><?php $this->e('Account') ?></label></th>
                        <td>
                            <select class="ga-profile-select" name="ga-account" id="ga-account" data-ga-account-id="<?php echo $google->ga_profile['account'] ?>" data-child="ga-profile" data-clear-target="1">
                                <option value="0"<?php selected(!$google->ga_profile['account']) ?>><?php $this->e('Please select') ?></option>
                                <?php foreach( $google->ga_accounts as $account ): ?>
                                <option value="<?php echo esc_attr($account->id) ?>"<?php selected( $account->id == $google->ga_profile['account'] ) ?>><?php echo esc_html($account->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <i class="dashicons dashicons-yes"></i>
                            <i class="dashicons dashicons-update"></i>
                            <?php if( !$google->ga_accounts ): ?>
                                <p class="description">
                                    <?php $this->e('Mmh, can\'t get Analytics accounts. Your token might be wrong.') ?>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="ga-profile"><?php $this->e('Profile') ?></label></th>
                        <td>
                            <select class="ga-profile-select" name="ga-profile" id="ga-profile" data-ga-profile-id="<?php echo $google->ga_profile['profile'] ?>" data-child="ga-view" data-clear-target="2">
                                <option value="0"<?php selected(!$google->ga_profile['profile']) ?>><?php $this->e('Please select') ?></option>
                            </select>
                            <i class="dashicons dashicons-yes"></i>
                            <i class="dashicons dashicons-update"></i>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="ga-view"><?php $this->e('View') ?></label></th>
                        <td>
                            <select name="ga-view" id="ga-view" data-ga-view-id="<?php echo $google->ga_profile['view'] ?>">
                                <option value="0"<?php selected(!$google->ga_profile['view']) ?>><?php $this->e('Please select') ?></option>
                            </select>
                            <i class="dashicons dashicons-yes"></i>
                            <i class="dashicons dashicons-update"></i>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <?php submit_button() ?>
            </form>
            <?php endif; ?>

            <h3>
                <i class="lsf lsf-server"></i> <?php $this->e('Create Database') ?>
            </h3>
            <p>
                <?php $this->e('Gianism can create a typical table for Google Analytics Ranking. If you need customized one, forget about it and create your own.') ?>
            </p>

            <table class="mysql-table">
                <caption><?php $this->e('Ranking table structure') ?></caption>
                <thead>
                <tr>
                    <th><?php $this->e('Column') ?></th>
                    <th><?php $this->e('Type') ?></th>
                    <th><?php $this->e('Length') ?></th>
                    <th><?php $this->e('Misc') ?></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <th scope="row">ID</th>
                    <td>BIGINT</td>
                    <td>20</td>
                    <td>PK, <code>auto_increment</code></td>
                </tr>
                <tr>
                    <th scope="row">category</th>
                    <td>VARCHAR</td>
                    <td>64</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <th scope="row">object_id</th>
                    <td>BIGINT</td>
                    <td>20</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <th scope="row">object_value</th>
                    <td>BIGINT</td>
                    <td>20</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <th scope="row">calc_date</th>
                    <td>DATE</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <th scope="row">created</th>
                    <td>TIMESTAMP</td>
                    <td>&nbsp;</td>
                    <td>Default CURRENT_TIMESTAMP</td>
                </tr>
                </tbody>
            </table>

            <?php if( $google->table_exists() ): ?>
                <p class="success">
                    <i class="dashicons dashicons-yes"></i> <?php $this->e('Table exits.') ?>
                </p>
            <?php else: ?>
                <form id="ga-table-create" action="<?php echo $google->table_create_url(admin_url('tools.php?page=gianism_ga')) ?>" method="post">
                    <?php wp_nonce_field($this->nonce_action('google_create-table'), $this->nonce_key_name) ?>

                    <?php submit_button($this->_('Create Table')) ?>
                </form>
            <?php endif; ?>

            <h3>
                <i class="lsf lsf-time"></i> <?php $this->e('Installed cron files') ?>
            </h3>

            <?php if( empty($google->crons) ): ?>
                <p><?php $this->e('Nothing is installed.') ?></p>
            <?php else: ?>
                <form id="cron-checker" method="post" action="<?php echo admin_url('admin-ajax.php') ?>">
                    <input type="hidden" name="action" value="<?php echo $google::AJAX_CRON ?>" />
                    <?php wp_nonce_field($google::AJAX_CRON) ?>
                    <table class="mysql-table">
                        <thead>
                        <tr>
                            <th><?php $this->e('Class Name') ?></th>
                            <th><?php $this->e('Category') ?></th>
                            <th><?php $this->e('Interval') ?></th>
                            <th><?php $this->e('Available') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $counter = 0; foreach($google->crons as $class_name): ?>
                            <tr>
                                <th scope="row">
                                    <label>
                                        <input type="radio" name="cron" value="<?php echo $counter ?>" />
                                        <?php echo $class_name ?>
                                    </label>
                                </th>
                                <td><?php echo $class_name::CATEGORY ?></td>
                                <td><?php echo $class_name::INTERVAL ?></td>
                                <td>
                                    <?php if($class_name::SKIP_CRON): ?>
                                    <span class="description"><i class="lsf lsf-ban"></i> No</span>
                                    <?php else: ?>
                                    <span class="success"><i class="lsf lsf-check"></i> Yes</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php $counter++; endforeach; ?>
                        </tbody>
                    </table>
                    <?php submit_button($this->_('Check Data')) ?>
                    <pre></pre>
                </form>
            <?php endif; ?>

            <p class="description">
                <?php printf($this->_('Do you want to know how to install files? Here is <a href="%s">blog post</a> about it.'), 'http://takahashifumiki.com/web/programing/3184/') ?>
            </p>


        <?php endif; ?>



    </div><!-- //.main-content -->

    <br class="clear" />

</div><!-- //.gianism-wrap -->