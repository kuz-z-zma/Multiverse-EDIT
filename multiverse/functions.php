<?php
/**
 * Prints jQuery JS to enable the toggling of search results of Zenpage  items
 *
 */
function printZDSearchToggleJS() {
  ?>
  <script>
  function toggleExtraElements(category, show) {
    if (show) {
      jQuery('.' + category + '_showless').show();
      jQuery('.' + category + '_showmore').hide();
      jQuery('.' + category + '_extrashow').show();
    } else {
      jQuery('.' + category + '_showless').hide();
      jQuery('.' + category + '_showmore').show();
      jQuery('.' + category + '_extrashow').hide();
    }
  }
  </script>
  <?php
}

/**
 * Prints the "Show more results link" for search results for Zenpage items
 *
 * @param string $option "news" or "pages"
 * @param int $number_to_show how many search results should be shown initially
 */
function printZDSearchShowMoreLink($option, $number_to_show) {
  $option = strtolower($option);
  switch ($option) {
    case "news":
    $num = getNumNews();
    break;
    case "pages":
    $num = getNumPages();
    break;
  }
  if ($num > $number_to_show) {
    ?>
    <a class="<?php echo $option; ?>_showmore"href="javascript:toggleExtraElements('<?php echo $option; ?>',true);"><?php echo gettext('Show more results'); ?></a>
    <a class="<?php echo $option; ?>_showless" style="display: none;" href="javascript:toggleExtraElements('<?php echo $option; ?>',false);"><?php echo gettext('Show fewer results'); ?></a>
    <?php
  }
}

/**
 * Adds the css class necessary for toggling of Zenpage items search results
 *
 * @param string $option "news" or "pages"
 * @param string $c After which result item the toggling should begin. Here to be passed from the results loop.
 */
function printZDToggleClass($option, $c, $number_to_show) {
  $option = strtolower($option);
  $c = sanitize_numeric($c);
  if ($c > $number_to_show) {
    echo ' class="' . $option . '_extrashow" style="display:none;"';
  }
}


zp_register_filter('theme_head', 'css_head', 500);
zp_register_filter('theme_body_close', 'multiverse');

/**
 *
 * Set viewport & load CSS
 * @author bic-ed
 *
 */
function css_head() {
  global $_zp_themeroot;
  ?>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="<?php echo pathurlencode($_zp_themeroot . '/css/multi.css') ?>">
  <?php
}

function my_checkPageValidity($request, $gallery_page, $page) {
  switch ($gallery_page) {
    case 'gallery.php':
    $gallery_page = 'index.php'; // same as an album gallery index
    break;
    case 'index.php':
    if (ZENPAGE_ON) {
      if (NEWS_IS_HOME) {
        $gallery_page = 'news.php'; // really a news page
        break;
      }
      if (PAGE_IS_HOME) {
        return $page == 1; // only one page if zenpage enabled.
      }
    }
    break;
    case 'news.php':
    case 'album.php':
    case 'search.php':
    break;
    default:
    if ($page != 1) {
      return false;
    }
  }
  return checkPageValidity($request, $gallery_page, $page);
}

/**
 * makex news page 1 link go to the index page
 * @param type $link
 * @param type $obj
 * @param type $page
 */
function newsOnIndex($link, $obj, $page) {
  if (is_string($obj) && $obj == 'news.php') {
    if (MOD_REWRITE) {
      if (preg_match('~' . _NEWS_ . '[/\d/]*$~', $link)) {
        $link = WEBPATH . '/';
        if ($page > 1)
        $link .=  _PAGE_ . '/' . $page;
      }
    } else {
      if (strpos($link, 'category=') === false && strpos($link, 'title=') === false) {
        $link = str_replace('?&', '?', rtrim(str_replace('p=news', '', $link), '?'));
      }
    }
  }
  return $link;
}

if (!OFFSET_PATH) {
  enableExtension('print_album_menu', 1 | THEME_PLUGIN, false);
  setOption('user_logout_login_form', 2, false);
  define('ZENPAGE_ON', extensionEnabled('zenpage'));
  $_zp_page_check = 'my_checkPageValidity';
  if (ZENPAGE_ON) {
    define('PAGE_IS_HOME', getOption('zenpage_homepage'));
    define('NEWS_IS_HOME', getOption('zenpage_zp_index_news'));
    if (NEWS_IS_HOME) {  // only one index page if zenpage plugin is enabled & displaying
      zp_register_filter('getLink', 'newsOnIndex');
    }
  }
}


// disable contact form unwanted fields
setOption('contactform_title','omitted',false);
setOption('contactform_city','omitted',false);
setOption('contactform_state','omitted',false);
setOption('contactform_company','omitted',false);
setOption('contactform_street','omitted',false);
setOption('contactform_postal','omitted',false);
setOption('contactform_country','omitted',false);
setOption('contactform_website','omitted',false);
setOption('contactform_phone','omitted',false);
setOption('contactform_confirm','0',false);
setOption('contactform_email','required',false);
setOption('contactform_name','required',false);
setOption('tinymce4_comments',null,false);

// Set email subject if the theme option is filled
$mailsubject = ($mailsubject = getThemeOption('email_subject')) ? $mailsubject : "";

/**
 *
 * Defines variables and loads javascript file
 * @author bic-ed
 *
 */
function multiverse() {
  global $mailsubject, $_zp_themeroot, $_zp_gallery_page, $_zp_loggedin;

  // Some missing context sensitive menu behavior to be added via JavaScript:
  // $news_active = 1 -> Disable "All news" link in NewsCategories menu
  // $gallery_active = 1 -> Disable "Gallery index" link in Album menu
  $news_active = $gallery_active = 0;
  if (ZENPAGE_ON) {
    switch ($_zp_gallery_page) {
      case 'index.php':
      if (NEWS_IS_HOME) {
        $news_active = 1;
      } elseif (!PAGE_IS_HOME) {
        $gallery_active = 1;
      }
      break;
      case 'gallery.php':
      $gallery_active = 1;
      break;
    }
  } else {
    $gallery_active = 1;
  }

  $javas = array(
    'searchPlaceholder' => strtoupper(gettext("search")),
    'newsActive' => ($news_active ? 1 : 0),
    'galleryActive' => ($gallery_active ? 1 : 0),
    'contactURL' => WEBPATH . '/themes/multiverse/ajax/contact.php',
    'mailSubject' => $mailsubject,
    'mailSent' => get_language_string(getOption('contactform_thankstext')),
  );
if ($_zp_loggedin) { ?>
<link rel="stylesheet" href="<?php echo $_zp_themeroot . '/css/mv_ad_tb.min.css' ?>">
<?php } ?>
<script>
var phpToJS = <?php echo json_encode($javas) ?>;
</script>
<script src="<?php echo $_zp_themeroot; ?>/js/merged/multi.js"></script>
<?php
return;
}

/**
 * Detects if there is at least one link to print
 * for the next function printFooterRSS()
 * @var boolean
 */
$rss_links_enabled = false;
if (class_exists('RSS') && !OFFSET_PATH) {
  // Get needed (here and later) RSS options
  $_rss_gallery = getOption('RSS_album_image');
  $_rss_news = ZENPAGE_ON && ZP_NEWS_ENABLED && getOption('RSS_articles');
  $rss_links_enabled = $_rss_gallery || $_rss_news;
}
/**
 * Prints RSS links in footer
 * @author bic-ed
 */
function printFooterRSS() {
  global $_zp_current_album, $rss_links_enabled, $_rss_gallery, $_rss_news;
  if ($rss_links_enabled) { ?>
    <li class="main-nav rss">
      <ul class="drop rss">
        <li>
          <a class="icon fa-rss">
            <span class="hide">RSS Feed</span>
          </a>
        </li>
      </ul>
      <ul>
        <?php
        if ($_rss_news) {
          printRSSLink("News", "<li>", gettext("News"), '</li>', false);
        }
        if ($_rss_gallery) {
          printRSSLink('Gallery', '<li>', gettext('Gallery'), '</li>', false);
          if (!is_null($_zp_current_album)) {
            printRSSLink('Album', '<li>', gettext('Album'), '</li>', false);
          }
        }
        ?>
      </ul>
    </li>
    <?php
  }
  return;
}
