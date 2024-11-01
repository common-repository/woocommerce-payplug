<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

if ( function_exists( 'woocommercepayplug_fs' ) ) {
    woocommercepayplug_fs()->set_basename( true, __FILE__ );
    return;
}


if ( !function_exists( 'woocommercepayplug_fs' ) ) {
    // Create a helper function for easy SDK access.
    function woocommercepayplug_fs()
    {
        global  $woocommercepayplug_fs ;
        
        if ( !isset( $woocommercepayplug_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/freemius/start.php';
            $woocommercepayplug_fs = fs_dynamic_init( array(
                'id'              => '204',
                'slug'            => 'woocommerce-payplug',
                'type'            => 'plugin',
                'public_key'      => 'pk_ffcee1234d27f439a1cfbff545cdc',
                'is_premium'      => false,
                'premium_suffix'  => '',
                'has_addons'      => false,
                'has_paid_plans'  => true,
                'trial'           => array(
                'days'               => 7,
                'is_require_payment' => false,
            ),
                'has_affiliation' => 'selected',
                'menu'            => array(
                'slug'       => 'wcpayplug-admin',
                'first-path' => 'options-general.php?page=wcpayplug-admin',
                'support'    => false,
                'parent'     => array(
                'slug' => 'options-general.php',
            ),
            ),
                'is_live'         => true,
            ) );
        }
        
        return $woocommercepayplug_fs;
    }
    
    // Init Freemius.
    woocommercepayplug_fs();
    // Signal that SDK was initiated.
    do_action( 'woocommercepayplug_fs_loaded' );
}

class WCPayplugSettingsPage
{
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_wcpayplug_page' ) );
        woocommercepayplug_fs()->add_filter(
            'connect_message_on_update',
            array( $this, 'override_connect_message_on_update' ),
            10,
            6
        );
        woocommercepayplug_fs()->add_filter(
            'connect_message',
            array( $this, 'override_connect_message' ),
            10,
            6
        );
        woocommercepayplug_fs()->add_filter( 'connect_url', array( $this, 'override_connect_url' ) );
    }
    
    public function add_wcpayplug_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'WooCommerce PayPlug',
            'PayPlug',
            'manage_options',
            'wcpayplug-admin',
            array( $this, 'create_wcpayplug_admin_page' )
        );
    }
    
    public function override_connect_url( $url )
    {
        return add_query_arg( array(
            'page' => 'wcpayplug-admin',
        ), admin_url( 'options-general.php' ) );
    }
    
    public function create_wcpayplug_admin_page()
    {
        wp_enqueue_style( 'wcpayplugcss', WCPAYPLUG_BASE_URL . 'assets/css/payplug.css' );
        ?>
        <div class="wrap wcpayplug_presentation">
            <h1>WooCommerce - PayPlug</h1>
            <div class="wcpayplug_col_1">
                <div class="wcpayplug_block wcpayplug_introduction">
                    <p>PayPlug est une <b>solution française de paiement en ligne</b>.<br>
                    Vous trouverez toutes les informations concernant les avantages et les tarifs sur <a href="https://www.payplug.com/tarifs" target="_blank">payplug.com</a>
                    </p>
                    <a href="http://url.wba.fr/payplug" target="_blank" class="button">Créer un compte PayPlug</a>
                </div><div class="wcpayplug_block wcpayplug_instructions">
                    <h2>Instruction de configuration</h2>
                    <p>
                        Sur la page de configuration de la passerelle de paiement PayPlug :
                        <ol>
                            <li>Indiquez votre identifiant PayPlug et votre mot de passe PayPlug dans les champs respectifs</li>
                            <li>Cliquez sur le bouton 'Enregistrer les changements' en bas de la page</li>
                        </ol>
                        Par mesure de sécurité, votre mot de passe PayPlug n'est pas sauvegardé dans votre base de données.<br>
                        Vous devrez le renseigner à chaque nouvelle modification<br>(ex : Activation/Désactivation du mode TEST)
                    </p>
                    <a href="<?php 
        echo  add_query_arg( array(
            'page'    => 'wc-settings',
            'tab'     => 'checkout',
            'section' => 'woocommerce-payplug',
        ), admin_url( 'admin.php' ) ) ;
        ?>" class="button button-primary">Accéder à la page de configuration</a>
                </div>
            </div>
            <div class="wcpayplug_col_2">
                <h2 class="title" id="version-pro">WooCommerce - PayPlug PRO</h2>
                <h2>Le paiement fractionné garanti avec Oney</h2>
                <h3>Proposez à vos clients de régler en 3 ou 4 fois</h3>
                <p>
                La façon la plus simple d'augmenter vos ventes
                <br>
                <a href="https://www.payplug.com/fr/paiement-fractionne-garanti" target="_blank">en savoir plus</a>
                <br>
                <br>
                <b>! Vous devez disposez d'un compte PayPlug Premium pour avoir accès à cette option !</b>
                
                <br>


                <ol>
                    <li><a href="<?php 
        echo  add_query_arg( array(
            'page'  => 'wcpayplug-admin-pricing',
            'trial' => 'true',
        ), admin_url( 'options-general.php' ) ) ;
        ?>">Vous pouvez tester la version PRO pendant 7 jours sans avoir à fournir d'information de paiement</a></li>
                    <li><a href="<?php 
        echo  add_query_arg( array(
            'page' => 'wcpayplug-admin-pricing',
        ), admin_url( 'options-general.php' ) ) ;
        ?>">Vous pouvez demander à être remboursé pendant 14 jours sans avoir à fournir la moindre explication</a></li>
                </ol>
                </p>

                <a href="<?php 
        echo  add_query_arg( array(
            'page'  => 'wcpayplug-admin-pricing',
            'trial' => 'true',
        ), admin_url( 'options-general.php' ) ) ;
        ?>" class="button button-primary">Commencer l'essai gratuit</a>

                <p>L'interface permettant de bénéficier de la version PRO n'est disponible qu'en anglais pour le moment. N'hésitez pas à nous contacter si vous avez besoin d'un coup de main.
                <br>
                Cochez la case "Pre-Sale Question" et indiquer le titre de votre message dans le champ 'Summary' puis le contenu de votre message dans le champ en dessous :
                <a href="<?php 
        echo  add_query_arg( array(
            'page' => 'wcpayplug-admin-contact',
        ), admin_url( 'options-general.php' ) ) ;
        ?>" class="button primary-button">Accéder au support</a>

                </p>
            </div>
        </div>
        <?php 
    }
    
    public function override_connect_message_on_update(
        $original,
        $first_name,
        $plugin_name,
        $login,
        $link,
        $freemius_link
    )
    {
        return sprintf( __( 'Bonjour %s', 'woocommerce-payplug' ), $first_name ) . '<br>' . sprintf(
            __( '<h3>Merci d&apos;avoir installé la dernière version de %1$s</h3>Afin de nous permettre d&apos;améliorer notre passerelle de paiement, nous vous demandons l&apos;autorisation d&apos;avoir quelques informations sur votre site. Ces informations ne seront en aucun cas céder à des tiers.
                <br><br>Vous pouvez passer cette étape en cliquant sur le bouton "Passer", le plugin marchera tout aussi bien !', 'woocommerce-payplug' ),
            '<b>' . $plugin_name . '</b>',
            '<b>' . $login . '</b>',
            $link,
            $freemius_link
        );
    }
    
    public function override_connect_message(
        $original,
        $first_name,
        $plugin_name,
        $login,
        $link,
        $freemius_link
    )
    {
        return sprintf( __( 'Bonjour %s', 'woocommerce-payplug' ), $first_name ) . '<br>' . sprintf(
            __( '<h3>Merci d&apos;avoir installé %1$s</h3>Afin de nous permettre d&apos;améliorer notre passerelle de paiement, nous vous demandons l&apos;autorisation d&apos;avoir quelques informations sur votre site. Ces informations ne seront en aucun cas céder à des tiers.
                <br><br>Vous pouvez passer cette étape en cliquant sur le bouton "Skip", le plugn marchera tout aussi bien !', 'woocommerce-payplug' ),
            '<b>' . $plugin_name . '</b>',
            '<b>' . $login . '</b>',
            $link,
            $freemius_link
        );
    }

}
if ( is_admin() ) {
    $wcpayplug_settings_page = new WCPayplugSettingsPage();
}