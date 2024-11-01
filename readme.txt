=== WooCommerce PayPlug ===
Contributors: BorisColombier, freemius
Donate link: http://wba.fr/
Tags: WooCommerce, Payment, Gateway, Credit Cards, Shopping Cart, PayPlug, Extension
Requires at least: 3.0.1
Tested up to: 5.4.2
Stable tag: 3.5.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Acceptez les paiements par carte bancaire sur votre boutique WooCommerce avec PayPlug

== Description ==

>La solution de paiement par carte bancaire la plus simple et
>efficace pour votre boutique WooCommerce

[PayPlug](https://www.payplug.fr/ "PayPlug") propose une solution de paiement très avantageuse pour les activités basées en Europe.

* Pas de frais d’installation
* Compatible avec Wordpress Multisite

Créer un [compte PayPlug gratuit](http://url.wba.fr/payplug "compte PayPlug gratuit")

version minimum de WooCommerce : 2.6.0


== Installation ==

1. Créez [un compte payPlug gratuit](http://url.wba.fr/payplug/ "compte PayPlug gratuit")
2. Installez le plugin via le manager Wordpress ou FTP
3. Activez le plugin
4. Allez dans les paramètres WooCommerce > onglet 'Commande'
5. Cliquez sur 'PayPlug' et indiquez vos identifiants et mot de passe PayPlug

== Personnalisation des champs de checkout ==
Lors d'une demande de paiement avec PayPlug, certains champs sont obligatoires.
L'adresse, le code postal, la ville et le pays par exemple.
Si vous avez personnalisé votre boutique afin de ne pas demander ces informations, vous devez indiquer des valeurs par défaut via le hook 'woocommerce-payplug_filter_billing_datas'
ex :
    add_filter( 'woocommerce-payplug_filter_billing_datas', function($billing_datas){
        $billing_datas['address1'] = 'empty';
        $billing_datas['postcode'] = '11111';
        $billing_datas['city'] = 'empty';
        $billing_datas['country'] = 'FR';
        return $billing_datas;
    });

à placer dans votre fichier functions.php

== Screenshots ==

1. Configuration screen
2. Public view

== Changelog ==

= 3.5.3 =
* Update Freemius SDK

= 3.5.2 =
* Fix 'Format de téléphone non valide'

= 3.5.1 =
* Update Freemius SDK

= 3.5.0 =
* Update WordPress and WooCommerce version
* Add filter for custom checkout fields

= 3.4.0 =
* Add filter for custom checkout fields
* Update PayPlug API

= 3.3.0 =
* Update PayPlug API

= 3.2.0 =
* Security Fix

= 3.1.0 =
* Add gateway quick switch
* Add free trial version

= 3.0.0 =
* Update to lastest PayPlug API

= 2.7.0 =
* Force update for payment url
* Add Cancel url 
* Add support form

= 2.6.2 =
* Fix debug

= 2.6.1 =
* Fix error

= 2.6.0 =
* Add debug log
* Fix Compatibility WooCommerce 3.x

= 2.5.1 =
* Fix missing cards image

= 2.5.0 =
* Nouvel icône plus rassurant sur la page de commande pour les clients
* Ajout d'une autre possibilité pour la configuration
* Compatible avec WooCommerce 2.5.5
