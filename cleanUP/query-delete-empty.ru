WITH <http://linked.opendata.cz/resource/dataset/nusl.cz> DELETE {
?s ?p ?o.
}
WHERE {
?s ?p ?o.
FILTER (?o = '')
}