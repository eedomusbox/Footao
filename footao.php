<?php

/* * ***********************************************************************************
 * Téléchargement des retransmissions des match de foot
 * Développement par eedomusbox@gmail.com
 *
 * Versionning
 *  V1.0: Version initiale
 *
 * Utilisation
 *  footao.php
 *  footao.php?ville=Marseille_Barcelone
 *  Pour changer de jour, il faut mettre le paramètre jour avec le nombre de jour en plus
 *  par rapport à aujourd'hui: &jour=4
 *  Pour changer de recherche, il faut utiliser le paramète ville avec les villes que l'on
 *  recherche séparé par un "_" : Exemple : ville=Toulouse_Milan
 * *********************************************************************************** */

// Chargement des données et fonctions
require_once ('../model/_include/simple_html_dom.php');
require_once '../model/_3rdParty/PHPmailer/PHPMailerAutoload.php';

// Variable
$bodyMail = '';

// Jour de recherche
if ( isset ( $_GET["jour"] ) ) {
    $jour = $_GET["jour"];

    if ( ! ctype_digit ( $jour ) ) {
        $printS .= "Cette variable '&jour' n'est pas un entier";
        print_r ( $printS );
        exit;
    }
    $nbjour = 0;
    if ( $jour == '1234567' ) {
        $nbJour = 6;
    }

// Cas de la semaine entière
    for ( $i = 0; $i <= $nbJour; $i ++ ) {
        if ( $nbJour == 0 ) {
            $jourRecherche = $jour;
        } else {
            $jourRecherche = $i;
        }
        $date = date ( strtotime ( "+$jourRecherche day" ) );
        $fmt = new IntlDateFormatter ( "fr_FR", IntlDateFormatter::FULL, IntlDateFormatter::MEDIUM );
        $fmt->setPattern ( "EEEE-d-MMMM-yyyy" );
        $datea = ucfirst ( $fmt->format ( $date ) );
        $fmt->setPattern ( "dd" );
        $jr = ucfirst ( $fmt->format ( $date ) );
        $fmt->setPattern ( "MM" );
        $ms = ucfirst ( $fmt->format ( $date ) );
        $fmt->setPattern ( "yyyy" );
        $an = ucfirst ( $fmt->format ( $date ) );
        $fmt->setPattern ( "EEEE d MMMM yyyy" );
        $date2 = ucfirst ( $fmt->format ( $date ) );
        $lien[$i] = ['https://www.footao.tv/match-foot.php?v=' . $datea . '&jr=' . $jr . '&ms=' . $ms . '&an=' . $an, $date2];
        $datea = $date2;
    }
} else {
//Cas de la semaine en contab
    if ( isset ( $_SERVER['argv'][1] ) ) {
        $nbJour = 6;
// Cas de la semaine entière
        for ( $i = 0; $i <= $nbJour; $i ++ ) {
            if ( $nbJour == 0 ) {
                $jourRecherche = $jour;
            } else {
                $jourRecherche = $i;
            }
            $date = date ( strtotime ( "+$jourRecherche day" ) );
            $fmt = new IntlDateFormatter ( "fr_FR", IntlDateFormatter::FULL, IntlDateFormatter::MEDIUM );
            $fmt->setPattern ( "EEEE-d-MMMM-yyyy" );
            $datea = ucfirst ( $fmt->format ( $date ) );
            $fmt->setPattern ( "dd" );
            $jr = ucfirst ( $fmt->format ( $date ) );
            $fmt->setPattern ( "MM" );
            $ms = ucfirst ( $fmt->format ( $date ) );
            $fmt->setPattern ( "yyyy" );
            $an = ucfirst ( $fmt->format ( $date ) );
            $fmt->setPattern ( "EEEE d MMMM yyyy" );
            $date2 = ucfirst ( $fmt->format ( $date ) );
            $lien[$i] = ['https://www.footao.tv/match-foot.php?v=' . $datea . '&jr=' . $jr . '&ms=' . $ms . '&an=' . $an, $date2];
            $datea = $date2;
        }
    } else {
        $lien[0] = ['https://www.footao.tv/match-foot-ce-soir-tv.htm', 'Ce soir'];
    }
}


// Récupération des paramètres "Ville à lire", séparé par un '_'
if ( isset ( $_GET["ville"] ) ) {

    $regex = "#";

    if ( preg_match ( "#_#", $_GET["ville"] ) ) {
        foreach ( explode ( '_', $_GET["ville"] ) as $str ) {
            $regex .= $str . '|';
        }
        $regex = substr ( $regex, 0, -1 );
    } else {
        $regex .= $_GET["ville"];
    }
    $regex .= "#";
} else {
    $regex = "#Marseille|Saint-Etienne|Barcelone#";
}

$printS .= R_CHARIOT . "Regex: $regex";

foreach ( $lien as $link ) {

    $printS .= R_TRAIT . ALIGN_CENTER . R_CHARIOT . TEXT_REQUETE . format_url_return ( $link[0] ) . FERME_P;

// Lecture du site
    $curl = curl_init ();
    curl_setopt ( $curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; rv:19.0) Gecko/20100101 Firefox/19.0" );
    curl_setopt ( $curl, CURLOPT_SSL_VERIFYPEER, FALSE );
    curl_setopt ( $curl, CURLOPT_HEADER, false );
    curl_setopt ( $curl, CURLOPT_FOLLOWLOCATION, true );
    curl_setopt ( $curl, CURLOPT_URL, $link[0] );
    curl_setopt ( $curl, CURLOPT_REFERER, $link[0] );
    curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, TRUE );
    $str = curl_exec ( $curl );
    curl_close ( $curl );

// Create a DOM object
    $html_base = new simple_html_dom();
// Load HTML from a string
    $html_base->load ( $str );

    foreach ( $html_base->find ( 'div[itemscope]' ) as $div ) {

        foreach ( $div->find ( 'time[itemprop=startDate]' ) as $time ) {
            break;
        }
        foreach ( $div->find ( 'img' ) as $chaine ) {
            $printChain = str_replace ( "tv direct match", " ", $chaine->alt );
//Afficher le logo
            $style2 = fct_switch ( $chaine->class );

            foreach ( $div->find ( 'a' ) as $match ) {
                if ( preg_match ( $regex, $match->plaintext, $matches ) && $match->plaintext <> '' && ! preg_match ( "#-19#", $match->plaintext ) && ! preg_match ( "#Fém#", $match->plaintext ) ) {
                    $bodyMail .= R_CHARIOT . $link[1] . R_CHARIOT;
                    $bodyMail .= "<table>";
                    $bodyMail .= "<tr>";
                    $bodyMail .= '<td style="color:Tomato;">' . $time->plaintext . ' </td>';
                    $bodyMail .= '<td>' . $style2 . '<img src="http://www.footao.tv/it.png"  alt="' . $printChain . '">' . '</div' . '</td>';
                    $bodyMail .= '<td style="color:navy;">' . $match->plaintext . '  est sur ' . $printChain . ' </td>';
                    $bodyMail .= "</tr></table>";
                }
            }
        }
    }
// }
    unset ( $curl );
    unset ( $str );
    unset ( $html_base );
}

$printS .= $bodyMail;
$printS = str_replace ( "navy", "white", $printS );
print_r ( $printS );

if ( $bodyMail != '' ) {
// Récupération du destinataire du mail
    $destinataireEmail = 'h';
    fct_get_destinataire ( $_GET['email'], $destinataireEmail );
    if ( empty ( $datea ) ) {
        $fmt = new IntlDateFormatter ( "fr_FR", IntlDateFormatter::FULL, IntlDateFormatter::MEDIUM );
        $fmt->setPattern ( "EEEE dd MMMM yyyy" );
        $datea = ucfirst ( $fmt->format ( time () ) );
    }

    if ( $nbJour == 6 ) {
        $titreMail = json_decode ( '"\u26BD"' ) . " Match de foot de la semaine "; // . json_decode ( '"\uD83D\uDCFA"' );
    } else {
        $titreMail = json_decode ( '"\u26BD"' ) . " Match de foot $datea "; //. json_decode ( '"\uD83D\uDCFA"' );
    }
    echo fct_mail_send_generique ( $destinataireEmail, $titreMail, $bodyMail, 'Foot' );
}

function fct_switch ( $variable ) {
    $style = '<div style="width:60px;height: 15px;background: url(http://www.footao.tv/i.png?a=2) no-repeat;vertical-align: -35%;margin-left: 2.2%; outline-offset: 2px;  outline: #f0f0f0 solid 1px;background-position: XXX">';
    $explode = explode ( ' ', $variable );
    switch ( $explode[1] ) {

        case 'b1':
            $style2 = str_replace ( "XXX", "0 0;", $style );
            break;
        case 'b2':
            $style2 = str_replace ( "XXX", "0 -15px;", $style );
            break;
        case 'c';
            $style2 = str_replace ( "XXX", "-60px 0;", $style );
            break;
        case 'e';
            $style2 = str_replace ( "XXX", "-120px 0;", $style );
            break;
        case 'r1';
            $style2 = str_replace ( "XXX", "-180px 0;", $style );
            break;
        case 'cs';
            $style2 = str_replace ( "XXX", "-60px -15px;", $style );
            break;
        case 'e2';
            $style2 = str_replace ( "XXX", "-120px -15px;", $style );
            break;
        case 'r2';
            $style2 = str_replace ( "XXX", "-180px -15px;", $style );
            break;
        case 'b3';
            $style2 = str_replace ( "XXX", "0 -30px;", $style );
            break;
        case 'b4';
            $style2 = str_replace ( "XXX", "0 -45px;", $style );
            break;
        case 'b5';
            $style2 = str_replace ( "XXX", "0 -60px;", $style );
            break;
        case 'b6';
            $style2 = str_replace ( "XXX", "0 -75px;", $style );
            break;
        case 'b7';
            $style2 = str_replace ( "XXX", "0 -90px;", $style );
            break;
        case 'b8';
            $style2 = str_replace ( "XXX", "0 -105px;", $style );
            break;
        case 'b9';
            $style2 = str_replace ( "XXX", "0 -120px;", $style );
            break;
        case 'b10';
            $style2 = str_replace ( "XXX", "0 -135px;", $style );
            break;
        case 'bw';
            $style2 = str_replace ( "XXX", "0 -150px;", $style );
            break;
        case 'w9';
            $style2 = str_replace ( "XXX", "0px -180px;", $style );
            break;
        case 'm6';
            $style2 = str_replace ( "XXX", "0px -195px;", $style );
            break;
        case 'mp';
            $style2 = str_replace ( "XXX", "0px -210px;", $style );
            break;
        case 'c';
            $style2 = str_replace ( "XXX", "-60px 0px;", $style );
            break;
        case 'cs';
            $style2 = str_replace ( "XXX", "-60px -15px;", $style );
            break;
        case 'cd';
            $style2 = str_replace ( "XXX", "-60px -30px;", $style );
            break;
        case 'f';
            $style2 = str_replace ( "XXX", "-60px -45px;", $style );
            break;
        case 'f2';
            $style2 = str_replace ( "XXX", "-60px -60px;", $style );
            break;
        case 'f3';
            $style2 = str_replace ( "XXX", "-60px -75px;", $style );
            break;
        case 'f4';
            $style2 = str_replace ( "XXX", "-60px -90px;", $style );
            break;
        case 'f5';
            $style2 = str_replace ( "XXX", "-60px -105px;", $style );
            break;
        case 'f6';
            $style2 = str_replace ( "XXX", "-60px -120px;", $style );
            break;
        case 'f7';
            $style2 = str_replace ( "XXX", "-60px -135px;", $style );
            break;
        case 'cst';
            $style2 = str_replace ( "XXX", "-60px -150px;", $style );
            break;
        case 'c8';
            $style2 = str_replace ( "XXX", "-60px -165px;", $style );
            break;
        case 'mc';
            $style2 = str_replace ( "XXX", "-60px -180px;", $style );
            break;
        case 'lw';
            $style2 = str_replace ( "XXX", "-60px -195px;", $style );
            break;
        case 'l';
            $style2 = str_replace ( "XXX", "-60px -210px;", $style );
            break;
        case 'e';
            $style2 = str_replace ( "XXX", "-120px 0px;", $style );
            break;
        case 'e2';
            $style2 = str_replace ( "XXX", "-120px -15px;", $style );
            break;
        case 'e3';
            $style2 = str_replace ( "XXX", "-120px -30px;", $style );
            break;
        case 'ep';
            $style2 = str_replace ( "XXX", "-120px -45px;", $style );
            break;
        case 'fr3';
            $style2 = str_replace ( "XXX", "-120px -60px;", $style );
            break;
        case 'fr3r';
            $style2 = str_replace ( "XXX", "-120px -75px;", $style );
            break;
        case 'fr2';
            $style2 = str_replace ( "XXX", "-120px -90px;", $style );
            break;
        case 'frs';
            $style2 = str_replace ( "XXX", "-120px -105px;", $style );
            break;
        case 'frO';
            $style2 = str_replace ( "XXX", "-120px -120px;", $style );
            break;
        case 'fr4';
            $style2 = str_replace ( "XXX", "-120px -135px;", $style );
            break;
        case 'tm';
            $style2 = str_replace ( "XXX", "-120px -165px;", $style );
            break;
        case 'tx';
            $style2 = str_replace ( "XXX", "-120px -180px;", $style );
            break;
        case 'lc';
            $style2 = str_replace ( "XXX", "-120px -195px;", $style );
            break;
        case 'lc';
            $style2 = str_replace ( "XXX", "-120px -210px;", $style );
            break;

        case 'r1';
            $style2 = str_replace ( "XXX", "-180px 0px;", $style );
            break;
        case 'r2';
            $style2 = str_replace ( "XXX", "-180px -15px;", $style );
            break;

        case 'r3';
            $style2 = str_replace ( "XXX", "-180px -30px;", $style );
            break;
        case 'r4';
            $style2 = str_replace ( "XXX", "-180px -45px;", $style );
            break;
        case 'ra1';
            $style2 = str_replace ( "XXX", "-180px -60px;", $style );
            break;
        case 'ra2';
            $style2 = str_replace ( "XXX", "-180px -75px;", $style );
            break;

        case 'bf';
            $style2 = str_replace ( "XXX", "-180px -90px;", $style );
            break;
        case 'rs';
            $style2 = str_replace ( "XXX", "-180px -105px;", $style );
            break;
        case 'o';
            $style2 = str_replace ( "XXX", "-180px -120px;", $style );
            break;
        case 'ff';
            $style2 = str_replace ( "XXX", "-180px -135px;", $style );
            break;
        case 'tw';
            $style2 = str_replace ( "XXX", "-180px -150px;", $style );
            break;

        case 'fb';
            $style2 = str_replace ( "XXX", "-180px -165px;", $style );
            break;
        case 'y';
            $style2 = str_replace ( "XXX", "-180px -180px;", $style );
            break;
        case 'd';
            $style2 = str_replace ( "XXX", "-180px -195px;", $style );
            break;
        case 'twh';
            $style2 = str_replace ( "XXX", "-180px -205px;", $style );
            break;

        case 'r5';
            $style2 = str_replace ( "XXX", "-240px 0;", $style );
            break;
        case 'r6';
            $style2 = str_replace ( "XXX", "-240px -15px;", $style );
            break;
        case 'r7';
            $style2 = str_replace ( "XXX", "-240px -30px;", $style );
            break;
        case 'r8';
            $style2 = str_replace ( "XXX", "-240px -45px;", $style );
            break;
        case 'r9';
            $style2 = str_replace ( "XXX", "-240px -60px;", $style );
            break;
        case 'r10';
            $style2 = str_replace ( "XXX", "-240px -75px;", $style );
            break;
        case 'r11';
            $style2 = str_replace ( "XXX", "-240px -90px;", $style );
            break;
        case 'r12';
            $style2 = str_replace ( "XXX", "-240px -105px;", $style );
            break;
        case 'r13';
            $style2 = str_replace ( "XXX", "-240px -120px;", $style );
            break;
        case 'r14';
            $style2 = str_replace ( "XXX", "-240px -135px;", $style );
            break;
        case 'r15';
            $style2 = str_replace ( "XXX", "-240px -150px;", $style );
            break;
        case 'r16';
            $style2 = str_replace ( "XXX", "-240px -165px;", $style );
            break;
        case 'ass';
            $style2 = str_replace ( "XXX", "-240px -180px;", $style );
            break;
        case 'tr';
            $style2 = str_replace ( "XXX", "-240px -195px;", $style );
            break;
        case 'o1';
            $style2 = str_replace ( "XXX", "-240px -210px;", $style );
            break;

        case 'te0';
            $style2 = str_replace ( "XXX", "-300px 0px;", $style );
            break;
        case 'te1';
            $style2 = str_replace ( "XXX", "-300px -15px;", $style );
            break;
        case 'te2';
            $style2 = str_replace ( "XXX", "-300px -30px;", $style );
            break;
        case 'te3';
            $style2 = str_replace ( "XXX", "-300px -45px;", $style );
            break;
        case 'te4';
            $style2 = str_replace ( "XXX", "-300px -60px;", $style );
            break;
        case 'te5';
            $style2 = str_replace ( "XXX", "-300px -75px;", $style );
            break;
        case 'te6';
            $style2 = str_replace ( "XXX", "-300px -90px;", $style );
            break;
        case 'te7';
            $style2 = str_replace ( "XXX", "-300px -105px;", $style );
            break;
        case 'te8';
            $style2 = str_replace ( "XXX", "-300px -120px;", $style );
            break;
        case 'tbo';
            $style2 = str_replace ( "XXX", "-300px -210px;", $style );
            break;
        default:
            $style2 = str_replace ( "XXX", "-180px -90px;", $style );
    }
    return $style2;
}
