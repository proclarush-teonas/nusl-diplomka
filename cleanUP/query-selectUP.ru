PREFIX dcterms: <http://purl.org/dc/terms/>

    SELECT ?ident WHERE { 
      ?s dcterms:created ?o.
      ?s dcterms:identifier ?ident.
    }
    GROUP BY ?ident
    HAVING COUNT(?o) > 1
  