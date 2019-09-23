# Kashif's Sainsburys Web Crawler
This is my solution to the Sainsburys web crawler task. It uses CURL to load the HTML and has to allow cookies as the page doesn't load without cookies enabled. After that I run a function to parse the titles, prices, links and in that loop I sum up the total cost. Then I run another loop through the links to the content of the linked pages, pull out the description and get the kb size of the content of the page. I use a dom crawler that uses JQuery type selectors which makes it easier to extract nodes and elements from the raw HTML content.
To run the app you can execute index.php with PHP in a terminal. I have also provided a link below.

## Dependencies
I installed symfony/dom-crawler to crawl the web page and filter out the elements I needed and strip out data I wanted to exclude.
I also installed symfony/css-selector which was required for the above package.

### PHPUnit Test
There is only one test to confirm that the same number of items returned (descriptions, prices and URLs) are identical, usually 17 on their web page.
```
phpunit --bootstrap vendor\autoload.php tests\SainsburysTest
```

### Live URL Test
[test url](http://sainsburys.preview1.co.uk) - A test site that produces JSON output for this application