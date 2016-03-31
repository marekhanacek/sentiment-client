<?php

set_time_limit(0);

/** @var \Nette\DI\Container $container */
$container = require __DIR__ . '/../app/bootstrap.php';

/** @var Nette\Database\Context $db */
$db = $container->getByType('Nette\Database\Context');

/** @var PHPInsight\Sentiment $sentiment */
$sentiment = $container->getByType('PHPInsight\Sentiment');

$categoryUrls = [
    'https://forum.nette.org/en/f68-beginners',
    'https://forum.nette.org/en/f64-general-discussion',
    'https://forum.nette.org/en/f63-nette-application',
    'https://forum.nette.org/en/f69-routing',
    'https://forum.nette.org/en/f47-latte',
    'https://forum.nette.org/en/f67-forms',
    'https://forum.nette.org/en/f71-database-orm',
    'https://forum.nette.org/en/f46-tracy',
    'https://forum.nette.org/en/f38-ajax',
    'https://forum.nette.org/en/f66-authentication-and-authorization',
    'https://forum.nette.org/en/f72-configuration-and-dependency-injection',
    'https://forum.nette.org/en/f73-testing',
    'https://forum.nette.org/en/f70-add-ons-plugins-a-components',
    'https://forum.nette.org/en/f65-tips-tricks-and-tutorials',
    'https://forum.nette.org/en/f78-release-announcements-news',
    'https://forum.nette.org/en/f34-rfc',
    'https://forum.nette.org/en/f74-bug-reports',
    'https://forum.nette.org/en/f75-feature-requests',
    'https://forum.nette.org/en/f76-discussion-on-development',
    'https://forum.nette.org/en/f77-documentation',
    'https://forum.nette.org/en/f81-jobs',
    'https://forum.nette.org/en/f79-miscellaneous',
    'https://forum.nette.org/en/f80-archive',
];

$db->table('comments')->delete();

foreach($categoryUrls as $categoryUrl) {
    $categoryHtml = file_get_contents($categoryUrl);
    $categoryCrawler = new \Symfony\Component\DomCrawler\Crawler($categoryHtml);

    $pagesText = $categoryCrawler->filter('.pagelink')->text();
    preg_match_all('/\d+/', $pagesText, $matches);
    $pages = end($matches[0]);

    for($i = 1; $i <= $pages; $i++) {

        $categoryHtml = file_get_contents($categoryUrl.'?p='.$i);
        $categoryCrawler = new \Symfony\Component\DomCrawler\Crawler($categoryHtml);

        foreach($categoryCrawler->filter('div.inner div.box div.inbox table tbody tr td.tcl div.tclcon a') as $a) {
            $postLink = 'https://forum.nette.org/en/'.$a->getAttribute('href');
            $postHtml = file_get_contents($postLink);
            $postCrawler = new \Symfony\Component\DomCrawler\Crawler($postHtml);

            foreach($postCrawler->filter('#content div.inner div.blockpost') as $el) {
                $c = new \Symfony\Component\DomCrawler\Crawler($el);
                $comment = modifyComment($c->filter("div.postmsg")->html());
                $user = $c->filter("dl dt strong a")->text();
                $category = $sentiment->categorise($comment);
                $score = $sentiment->score($comment);

                $data = [
                    'user' => $user,
                    'comment' => $comment,
                    'category' => $category,
                    'pos' => $score['pos'],
                    'neu' => $score['neu'],
                    'neg' => $score['neg']
                ];
                $db->table('comments')->insert($data);
            }
        }
    }
}

echo "DONE".PHP_EOL;


function modifyComment($string) {
    $string = \Nette\Utils\Strings::trim($string);
    $string = preg_replace('#(<pre.*?>)[\s\S]*?(</pre>)#', '', $string);
    $string = strip_tags($string);
    $string = str_replace("\n", " ", $string);
    $string = preg_replace('/\s+/', ' ', $string);
    return $string;
}