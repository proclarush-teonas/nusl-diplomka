nusl-diplomka

Tento repozit�� obsahuje pr�ci na projektu zp��stupn�n� �ed� literatury jako linked data.

Data se stahuj� z N�rodn�ho �lo�i�t� �ed� literatury (NUSL).
Uk�zku form�tu sta�en�ch dat lze naj�t v adres��i "files_format_preview", stejn� tak jako uk�zku dat po XSLT transformaci. 
Pro XSLT je pou�it saxon a knihovna komunikuj�c� s n�m v jazyce PHP, oboje je takt� p�ilo�eno (adr. saxon, xml).

Adres�� bat_files obsahuje d�vky pro spu�t�n� PHP kter� stahuje a zpracov�v� data (soubor run-php.bat) a d�vku pro dump dat z Virtuosa a ulo�en� do souboru.

Soubor config.ini obsahuje prom�nn� kter� lze p�izp�sobit konkr�tn�m podm�nk�m spou�t�n� aplikace.

Soubor tps.php se star� o sta�en� a transformaci dat, tak� o plynul� pr�b�h aplikace.

Soubor upload.php za�izuje nahr�v�n� dat do grafu ve Virtuosu.