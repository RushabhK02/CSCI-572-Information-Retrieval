import networkx as nx
import math

G = nx.read_edgelist("C:\\Users\\rushk\\Desktop\\CS books\\CSCI 572\\HWs\\HW4\\shared\\solr-7.7.3\\example\\LATIMES\\edgeList.txt", create_using=nx.DiGraph())

pagerank = nx.pagerank(G, alpha=0.85, personalization=None, max_iter=30, tol=1e-06, nstart=None, weight='weight', dangling=None)

with open("C:\\Users\\rushk\\Desktop\\CS books\\CSCI 572\\HWs\\HW4\\shared\\solr-7.7.3\\example\\LATIMES\\external_pageRankFile.txt", "w", encoding="utf-8") as f:
    for pageid in pagerank:
        f.write("/home/rushk02/shared/solr-7.7.3/example/LATIMES/latimes/" + pageid + "=" + str(math.log(pagerank[pageid])) + "\n")


