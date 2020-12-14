import simplejson as simplejson
from bs4 import BeautifulSoup
import time
import requests
from random import randint
import urllib.parse
import json
import csv

USER_AGENT = {'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'}

class SearchEngine:
    @staticmethod
    def search(query, sleep=True):
        if sleep:  # Prevents loading too many pages too soon
            time.sleep(randint(1, 10))
        temp_url = '+'.join(query.split())  # for adding + between words for the query
        url = 'https://html.duckduckgo.com/html/?q=' + temp_url
        soup = BeautifulSoup(requests.get(url, headers=USER_AGENT).text, "html.parser")
        new_results = SearchEngine.scrape_search_result(soup)
        return new_results

    @staticmethod
    def scrape_search_result(soup):
        raw_results = soup.find_all("a", {"class": "result__a"})
        results = []
        count = 0
        # implement a check to get only 10 results and also check that URLs must not be duplicated
        for anchor in raw_results:
            url = anchor['href']
            o = urllib.parse.urlparse(url)
            d = urllib.parse.parse_qs(o.query)
            link = d['uddg'][0]
            count = count + 1
            link = link.replace("https", "http")
            if link.endswith('/'):
                link = link[:-1]
            link = link.lower()
            results.append(link)
            if count == 10:
                break
        return results


#############Driver code############
f = open("100QueriesSet4.txt", "r")
google_results = {}
with open('Google_Result4.json') as google:
    google_results = json.load(google)

# print(google_results)
data = {}
queries = 0
avg_match = 0
avg_percentage = 0
avg_coeff = 0

statistics = []
for line in f:
    queries = queries + 1
    line = line.strip('\n')
    line = line.strip('\t')
    line = line.strip()
    res = SearchEngine.search(line)
    data[line] = res
    google_links = google_results[line]
    clean_links = []
    for link in google_links:
        link = link.replace("https", "http")
        if link.endswith('/'):
            link = link[:-1]
        link = link.lower()
        clean_links.append(link)

    match = 0
    d2 = 0
    idx = 0
    for link in res:
        if clean_links.count(link) > 0:
            match = match + 1
            d2 = d2 + pow(idx - clean_links.index(link), 2)
        idx = idx+1

    statline = []
    statline.append("Query " + str(queries))
    statline.append(match)
    statline.append(match * 10)
    avg_match = avg_match + match
    avg_percentage = avg_percentage + (match*10)
    if match == 0 or (match == 1 and d2 > 0):
        statline.append(0)
    elif match == 1:
        statline.append(1)
        avg_coeff = avg_coeff + 1
    elif match > 1:
        value = 6*d2
        value = value/(match*(pow(match, 2)-1))
        value = 1 - value
        avg_coeff = avg_coeff + value
        statline.append(value)

    # statline.append(d2)
    # print(statline)
    statistics.append(statline)

# print(statistics)
f.close()
json_data = json.dumps(data)
jsonDataFile = open("hw1.json", "w")
jsonDataFile.write(simplejson.dumps(simplejson.loads(json_data), indent=4, sort_keys=True))
jsonDataFile.close()

with open('hw1.csv', 'w', newline='') as csvfile:
    record_writer = csv.writer(csvfile, delimiter=',', quotechar='"', quoting=csv.QUOTE_MINIMAL)
    record_writer.writerow(['Queries', 'Number of Overlapping Results', 'Percent Overlap', 'Spearman Coefficient'])
    for stats in statistics:
        record_writer.writerow(stats)

    record_writer.writerow(['Averages', avg_match/100, avg_percentage/100, avg_coeff/100])

# print(json_data)
####################################
