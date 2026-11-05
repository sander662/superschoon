/* Super Schoon – prijsberekening met kaartmodule en offerteaanvraag */
'use strict';
const START_TIJD=Date.now();

function berekenPrijs(m2){
  if(!(m2>=1)) return null;
  let p;
  if(m2<=50) p=190+210*(m2-1)/49;
  else if(m2<=100) p=400+7*(m2-50);
  else p=750+6*(m2-100);
  return Math.round(p);
}
const $=id=>document.getElementById(id);
const eur=n=>'€ '+n.toLocaleString('nl-BE');
const eur2=n=>'€ '+n.toLocaleString('nl-BE',{minimumFractionDigits:2,maximumFractionDigits:2});
const AANRIJ={1:0,2:40,3:65};
const ZONELIJST={
 1:[['Zemst',['Elewijt','Eppegem','Hofstade','Weerde']],['Mechelen',['Heffen','Hombeek','Leest','Muizen','Walem']],['Vilvoorde',['Peutie']],['Kapelle-op-den-Bos',['Nieuwenrode','Ramsdonk']],['Kampenhout',['Berg','Buken','Nederokkerzeel']],['Steenokkerzeel',['Melsbroek','Perk']],['Bonheiden',['Rijmenam']],['Sint-Katelijne-Waver',['Onze-Lieve-Vrouw-Waver']],['Willebroek',['Blaasveld','Heindonk','Tisselt']],['Boortmeerbeek',['Hever']],['Machelen',['Diegem']],['Grimbergen',['Humbeek','Beigem','Strombeek-Bever']]],
 2:[['Rumst',['Reet','Terhagen']],['Kontich',['Waarloos']],['Duffel',[]],['Putte',['Beerzel']],['Keerbergen',[]],['Haacht',['Tildonk','Wespelaar']],['Herent',['Veltem-Beisem','Winksele']],['Kortenberg',['Erps-Kwerps','Everberg','Meerbeek']],['Meise',['Wolvertem']],['Londerzeel',['Malderen','Steenhuffel']],['Puurs',['Puurs-Sint-Amands','Breendonk','Liezele','Sint-Amands','Lippelo','Oppuurs','Ruisbroek']],['Zaventem',['Nossegem','Sint-Stevens-Woluwe','Sterrebeek']],['Wemmel',[]],['Boom',[]],['Edegem',[]],['Aartselaar',[]],['Merchtem',['Brussegem','Hamme']]],
 3:[['Leuven',['Heverlee','Kessel-Lo','Wilsele','Wijgmaal']],['Lubbeek',['Binkom','Linden','Pellenberg']],['Tervuren',['Duisburg','Vossem']],['Oud-Heverlee',['Blanden','Haasrode','Vaalbeek','Sint-Joris-Weert']],['Boutersem',['Kerkom','Roosbeek','Vertrijk','Willebringen','Neervelp']],['Rotselaar',['Werchter','Wezemaal']],['Tremelo',['Baal']],['Heist-op-den-Berg',['Booischot','Hallaar','Itegem','Schriek','Wiekevorst']],['Berlaar',['Gestel']],['Lier',['Koningshooikt']],['Bornem',['Hingene','Mariekerke','Weert']],['Buggenhout',['Opstal']],['Opwijk',['Mazenzele']],['Mortsel',[]],['Kraainem',[]],['Wezembeek-Oppem',[]],['Boechout',['Vremde']],['Asse',['Bekkerzeel','Kobbegem','Mollem','Relegem','Zellik']],
  ['Brussel',['Anderlecht','Elsene','Etterbeek','Evere','Ganshoren','Jette','Koekelberg','Oudergem','Schaarbeek','Sint-Agatha-Berchem','Sint-Gillis','Sint-Jans-Molenbeek','Sint-Joost-ten-Node','Sint-Lambrechts-Woluwe','Sint-Pieters-Woluwe','Ukkel','Vorst','Watermaal-Bosvoorde','Laken','Neder-Over-Heembeek','Haren']],
  ['Antwerpen',['Berchem','Borgerhout','Deurne','Ekeren','Hoboken','Merksem','Wilrijk','Berendrecht','Zandvliet','Lillo','Borsbeek']]]
};
const LEZ=['Brussel','Antwerpen'];
// Reistijd vanaf Beekveldstraat 1a, Zemst, Google Maps, vertrek dinsdag 7u30 (opgezocht 05/11/2026)
// gemeente|min. reistijd|max. reistijd|km|deelgemeenten
const REIS=`Aalst|50|80|35|Gijzegem, Hofstade, Baardegem, Herdersem, Meldert, Moorsel, Erembodegem, Nieuwerkerken
Aalter|75|120|108|Bellem, Knesselare, Lotenhulle, Poeke, Ursel
Aarschot|45|75|37|Gelrode, Langdorp, Rillaar
Affligem|40|65|43|Essene, Hekelgem, Teralfene
Alken|70|110|111|
Alveringem|110|170|176|Hoogstade, Oeren, Sint-Rijkers, Beveren, Gijverinkhove, Izenberge, Leisele, Stavele
Anzegem|80|130|116|Gijzelbrechtegem, Ingooigem, Vichte, Kaster, Tiegem
Ardooie|100|150|129|Koolskamp
Arendonk|55|80|84|
As|75|120|96|Niel-bij-As
Assenede|55|80|81|Boekhoute, Bassevelde, Oosteeklo
Avelgem|90|140|126|Kerkhove, Waarmaarde, Outrijve, Bossuit
Baarle-Hertog|65|100|84|
Balen|65|100|87|Olmen
Beernem|80|110|115|Oedelem, Sint-Joris
Beerse|50|80|68|Vlimmeren
Beersel|40|65|44|Lot, Alsemberg, Dworp, Huizingen
Begijnendijk|35|60|32|Betekom
Bekkevoort|50|85|52|Assent, Molenbeek-Wersbeek
Beringen|60|90|93|Beverlo, Koersel, Paal
Berlare|55|85|42|Overmere, Uitbergen
Bertem|35|60|24|Korbeek-Dijle, Leefdaal
Bever|65|100|77|
Beveren-Kruibeke-Zwijndrecht|40|60|45|Bazel, Beveren, Burcht, Doel, Haasdonk, Kallo, Kieldrecht, Kruibeke, Melsele, Rupelmonde, Verrebroek, Vrasene, Zwijndrecht
Bierbeek|45|75|37|Korbeek-Lo, Lovenjoel, Opvelp
Bilzen-Hoeselt|75|110|122|Bilzen, Beverst, Eigenbilzen, Grote-Spouwen, Hees, Hoelbeek, Hoeselt, Kleine-Spouwen, Martenslinde, Mopertingen, Munsterbilzen, Rijkhoven, Romershoven, Rosmeer, Schalkhoven, Sint-Huibrechts-Hern, Waltwilder, Werm
Blankenberge|90|140|134|Uitkerke
Bocholt|90|150|111|Kaulille, Reppel
Brakel|75|130|76|Elst, Everbeek, Michelbeke, Nederbrakel, Opbrakel, Zegelsem, Parike
Brasschaat|40|65|46|
Brecht|40|65|59|Sint-Job-in-'t-Goor, Sint-Lenaarts
Bredene|100|160|135|
Bree|90|140|108|Beek, Gerdingen, Opitter, Tongerlo
Brugge|90|140|121|Koolkerke, Sint-Andries, Sint-Michiels, Assebroek, Sint-Kruis, Dudzele, Lissewege
Damme|75|110|110|Hoeke, Lapscheure, Moerkerke, Oostkerke, Sijsele
De Haan|100|140|142|Klemskerke, Wenduine, Vlissegem
De Panne|110|180|176|Adinkerke
Deerlijk|80|130|119|
Deinze|70|120|102|Astene, Bachte-Maria-Leerne, Gottem, Grammene, Hansbeke, Landegem, Meigem, Merendree, Nevele, Petegem-aan-de-Leie, Poesele, Sint-Martens-Leerne, Vinkt, Vosselare, Wontergem, Zeveren
Denderleeuw|45|70|46|Iddergem, Welle
Dendermonde|40|70|32|Appels, Baasrode, Grembergen, Mespelare, Oudegem, Schoonaarde, Sint-Gillis-bij-Dendermonde
Dentergem|80|130|113|Markegem, Oeselgem, Wakken
Dessel|60|85|85|
Destelbergen|55|90|79|Heusden
Diepenbeek|75|110|115|
Diest|55|90|56|Deurne, Kaggevinne, Molenstede, Schaffen, Webbekom
Diksmuide|110|170|165|Beerst, Esen, Kaaskerke, Keiem, Lampernisse, Leke, Nieuwkapelle, Oostkerke, Oudekapelle, Pervijze, Sint-Jacobs-Kapelle, Stuivekenskerke, Vladslo, Woumen
Dilbeek|40|60|36|Sint-Martens-Bodegem, Sint-Ulriks-Kapelle, Itterbeek, Groot-Bijgaarden, Schepdaal
Dilsen-Stokkem|85|130|107|Dilsen, Elen, Lanklaar, Rotem, Stokkem
Drogenbos|40|65|40|
Eeklo|65|100|93|
Erpe-Mere|45|65|53|Aaigem, Bambrugge, Burst, Erondegem, Erpe, Mere, Ottergem, Vlekkem
Essen|55|85|67|
Evergem|60|90|88|Ertvelde, Sleidinge, Kluizen
Gavere|75|110|99|Asper, Baaigem, Dikkelvenne, Semmerzake, Vurste
Geel|55|85|75|
Geetbets|65|100|67|Grazen, Rummen
Genk|75|120|91|
Gent|60|110|85|Mariakerke, Drongen, Wondelgem, Sint-Amandsberg, Oostakker, Desteldonk, Mendonk, Sint-Kruis-Winkel, Gentbrugge, Ledeberg, Afsnee, Sint-Denijs-Westrem, Zwijnaarde
Geraardsbergen|65|100|70|Goeferdinge, Moerbeke, Nederboelare, Onkerzele, Ophasselt, Overboelare, Viane, Zarlardinge, Grimminge, Idegem, Nieuwenhove, Schendelbeke, Smeerebbe-Vloerzegem, Waarbeke, Zandbergen
Gingelom|60|90|69|Boekhout, Jeuk, Kortijs, Montenaken, Niel-bij-Sint-Truiden, Vorsen, Borlo, Buvingen, Mielen-boven-Aalst, Muizen
Gistel|90|150|147|Moere, Snaaskerke, Zevekote
Glabbeek|60|100|61|Bunsbeek, Glabbeek-Zuurbemde, Kapellen, Attenrode
Grobbendonk|40|65|58|Bouwel
Haaltert|45|70|50|Denderhoutem, Heldergem, Kerksken
Halen|55|90|60|Loksbergen, Zelem
Halle|50|75|51|Buizingen, Lembeek
Hamme|45|65|56|Moerzeke
Hamont-Achel|90|130|131|Achel, Hamont
Harelbeke|80|130|122|Bavikhove, Hulste
Hasselt|70|110|105|Sint-Lambrechts-Herk, Wimmertingen, Kermt, Kortessem, Spalbeek, Kuringen, Stokrooie, Stevoort
Hechtel-Eksel|75|110|102|Hechtel, Eksel
Heers|70|110|87|Batsheers, Gutschoven, Heks, Horpmaal, Klein-Gelmen, Mechelen-Bovelingen, Mettekoven, Opheers, Rukkelingen-Loon, Vechmaal, Veulen
Hemiksem|35|65|33|
Herentals|45|70|62|Morkhoven, Noorderwijk
Herenthout|40|70|35|
Herk-de-Stad|60|100|64|Berbroek, Donk, Schulen
Herselt|50|75|40|Ramsel
Herstappe|70|110|91|
Herzele|55|85|62|Hillegem, Sint-Antelinks, Sint-Lievens-Esse, Steenhuize-Wijnhuize, Woubrechtegem, Ressegem, Borsbeke
Heusden-Zolder|65|100|96|Heusden, Zolder
Heuvelland|110|160|164|Nieuwkerke, Dranouter, Wulvergem, Wijtschate, Westouter, Kemmel, Loker
Hoegaarden|50|80|52|Meldert, Outgaarden
Hoeilaart|45|80|35|
Holsbeek|45|80|36|Kortrijk-Dutsel, Sint-Pieters-Rode, Nieuwrode
Hooglede|100|160|154|Gits
Hoogstraten|50|75|68|Meer, Minderhout, Wortel, Meerle
Horebeke|75|120|76|Sint-Kornelis-Horebeke, Sint-Maria-Horebeke
Houthalen-Helchteren|70|110|82|Helchteren, Houthalen
Houthulst|110|160|166|Klerken, Merkem
Hove|28|45|26|
Huldenberg|40|70|34|Loonbeek, Neerijse, Ottenburg, Sint-Agatha-Rode
Hulshout|40|65|34|Houtvenne, Westmeerbeek
Ichtegem|100|150|140|Bekegem, Eernegem
Ieper|100|150|159|Brielen, Dikkebus, Sint-Jan, Hollebeke, Voormezele, Zillebeke, Boezinge, Zuidschote, Elverdinge, Vlamertinge
Ingelmunster|90|140|127|
Izegem|90|150|132|Emelgem, Kachtem
Jabbeke|90|140|135|Snellegem, Stalhille, Varsenare, Zerkegem
Kalmthout|50|75|60|
Kapellen|40|60|47|
Kaprijke|60|85|89|Lembeke
Kasterlee|50|80|77|Lichtaart, Tielen
Kinrooi|100|150|118|Kessenich, Molenbeersel, Ophoven
Kluisbergen|85|140|119|Berchem, Kwaremont, Ruien, Zulzeke
Knokke-Heist|80|120|120|Knokke, Westkapelle, Heist, Ramskapelle
Koekelare|100|160|149|Bovekerke, Zande
Koksijde|110|170|169|Oostduinkerke, Wulpen
Kortemark|100|160|145|Handzame, Werken, Zarren
Kortenaken|60|100|60|Ransberg, Hoeleden, Kersbeek-Miskom, Waanrode
Kortrijk|85|130|127|Bissegem, Heule, Bellegem, Kooigem, Marke, Rollegem, Aalbeke
Kruisem|75|120|108|Huise, Kruishoutem, Nokere, Ouwegem, Wannegem-Lede, Zingem
Kuurne|85|130|125|
Laakdal|55|75|78|Eindhout, Vorst, Varendonk, Veerle
Laarne|55|90|78|Kalken
Lanaken|85|130|136|Gellik, Neerharen, Veldwezelt, Rekem
Landen|60|90|67|Eliksem, Ezemaal, Laar, Neerwinden, Overwinden, Rumsdorp, Wange, Waasmont, Walsbets, Walshoutem, Wezeren, Attenhoven, Neerlanden
Langemark-Poelkapelle|100|150|159|Bikschote, Langemark, Poelkapelle
Lebbeke|45|60|30|Denderbelle, Wieze
Lede|50|80|57|Impe, Oordegem, Smetlede, Wanzele
Ledegem|100|150|144|Rollegem-Kapelle, Sint-Eloois-Winkel
Lendelede|85|140|128|
Lennik|50|75|45|Gaasbeek, Sint-Kwintens-Lennik, Sint-Martens-Lennik
Leopoldsburg|65|100|93|Heppen
Lichtervelde|90|150|145|
Liedekerke|40|65|45|
Lierde|65|100|69|Deftinge, Sint-Maria-Lierde, Hemelveerdegem, Sint-Martens-Lierde
Lievegem|75|120|100|Zomergem, Oostwinkel, Ronsele, Lovendegem, Vinderhoute, Waarschoot
Lille|45|65|63|Gierle, Poederlee, Wechelderzande
Linkebeek|45|75|43|
Lint|26|40|24|
Linter|60|100|63|Drieslinter, Melkwezer, Neerhespen, Neerlinter, Orsmaal-Gussenhoven, Overhespen, Wommersom
Lochristi|60|90|78|Beervelde, Wachtebeke, Zaffelare, Zeveneken
Lokeren|50|80|65|Daknam, Eksaarde, Moerbeke
Lommel|75|120|100|
Lo-Reninge|120|190|165|Lo, Noordschote, Pollinkhove, Reninge
Lummen|55|90|66|Linkhout, Meldert
Maarkedal|80|130|112|Etikhove, Maarke-Kerkem, Nukerke, Schorisse
Maaseik|90|140|115|Neeroeteren, Opoeteren
Maasmechelen|80|130|103|Eisden, Leut, Mechelen-aan-de-Maas, Meeswijk, Opgrimbie, Vucht, Boorsem, Uikhoven
Maldegem|65|100|101|Adegem, Middelburg
Malle|45|70|59|Oostmalle, Westmalle
Meerhout|55|80|80|
Menen|90|140|140|Lauwe, Rekkem
Merelbeke-Melle|60|100|87|Bottelare, Gontrode, Lemberge, Melle, Melsen, Merelbeke, Munte, Schelderode
Merksplas|55|90|79|
Mesen|110|160|157|
Middelkerke|100|160|157|Wilskerke, Leffinge, Mannekensvere, Schore, Sint-Pieters-Kapelle, Slijpe, Lombardsijde, Westende
Mol|70|110|91|
Moorslede|100|150|148|Dadizele
Nazareth-De Pinte|70|110|97|De Pinte, Eke, Nazareth, Zevergem
Niel|28|45|23|
Nieuwerkerken|70|110|72|Binderveld, Kozen, Wijer
Nieuwpoort|100|160|161|Ramskapelle, Sint-Joris
Nijlen|40|65|31|Bevel, Kessel
Ninove|50|80|58|Appelterre-Eichem, Denderwindeke, Lieferinge, Nederhasselt, Okegem, Voorde, Pollare, Meerbeke, Neigem, Aspelare, Outer
Olen|50|75|68|
Oostende|100|150|147|Stene, Zandvoorde
Oosterzele|60|90|68|Balegem, Gijzenzele, Landskouter, Moortsele, Scheldewindeke
Oostkamp|85|130|123|Hertsberge, Ruddervoorde, Waardamme
Oostrozebeke|85|130|121|
Oudenaarde|80|130|108|Bevere, Edelare, Eine, Ename, Heurne, Leupegem, Mater, Melden, Mullem, Nederename, Volkegem, Welden
Oudenburg|90|150|142|Ettelgem, Roksem, Westkerke
Oudsbergen|80|130|95|Ellikom, Gruitrode, Meeuwen, Neerglabbeek, Opglabbeek, Wijshagen
Oud-Turnhout|55|80|82|
Overijse|45|85|38|
Pajottegem|50|85|49|Galmaarden, Gooik, Herfelingen, Herne, Kester, Leerbeek, Oetingen, Sint-Pieters-Kapelle, Tollembeek, Vollezele
Peer|85|130|96|Grote-Brogel, Kleine-Brogel, Wijchmaal
Pelt|80|130|107|Neerpelt, Overpelt, Sint-Huibrechts-Lille
Pepingen|55|80|47|Bogaarden, Heikruis, Elingen, Beert, Bellingen
Pittem|90|140|122|Egem
Poperinge|110|170|169|Reningelst, Krombeke, Proven, Roesbrugge-Haringe, Watou
Ranst|35|60|41|Broechem, Emblem, Oelegem
Ravels|60|100|81|Weelde, Poppel
Retie|55|80|81|
Riemst|80|120|133|Genoelselderen, Herderen, Kanne, Membruggen, Millen, Val-Meer, Vlijtingen, Vroenhoven, Zichen-Zussen-Bolder
Rijkevorsel|50|75|66|
Roeselare|100|160|151|Beveren, Oekene, Rumbeke
Ronse|85|140|88|
Roosdaal|50|75|48|Onze-Lieve-Vrouw-Lombeek, Pamel, Strijtem, Borchtlombeek
Schelle|35|60|30|
Scherpenheuvel-Zichem|55|90|51|Scherpenheuvel, Averbode, Zichem, Messelbroek, Testelt
Schilde|35|65|43|'s-Gravenwezel
Schoten|35|60|43|
Sint-Genesius-Rode|50|80|49|
Sint-Gillis-Waas|40|60|53|De Klinge, Meerdonk, Sint-Pauwels
Sint-Laureins|70|100|100|Sint-Jan-in-Eremo, Sint-Margriete, Waterland-Oudeman, Watervliet
Sint-Lievens-Houtem|55|85|62|Letterhoutem, Bavegem, Zonnegem, Vlierzele
Sint-Martens-Latem|70|120|92|Deurle
Sint-Niklaas|45|65|55|Nieuwkerken-Waas, Belsele, Sinaai
Sint-Pieters-Leeuw|45|65|41|Oudenaken, Sint-Laureins-Berchem, Ruisbroek, Vlezenbeek
Sint-Truiden|75|120|76|Aalst, Brustem, Engelmanshoven, Gelinden, Groot-Gelmen, Halmaal, Kerkom-bij-Sint-Truiden, Ordingen, Zepperen, Duras, Gorsem, Runkelen, Wilderen, Velm
Spiere-Helkijn|90|140|138|Helkijn, Spiere
Stabroek|40|60|51|Hoevenen
Staden|110|170|159|Oostnieuwkerke, Westrozebeke
Stekene|45|65|60|Kemzeke
Temse|40|65|28|Elversele, Steendorp, Tielrode
Ternat|35|60|38|Sint-Katherina-Lombeek, Wambeek
Tessenderlo-Ham|60|80|86|Kwaadmechelen, Oostham, Tessenderlo
Tielt|85|130|114|Aarsele, Kanegem, Meulebeke, Schuiferskapelle
Tielt-Winge|50|85|49|Houwaart, Meensel-Kiezegem, Sint-Joris-Winge, Tielt
Tienen|55|90|54|Bost, Goetsenhoven, Hakendover, Kumtich, Oorbeek, Oplinter, Sint-Margriete-Houtem, Vissenaken
Tongeren-Borgloon|85|120|124|Berg, Bommershoven, Borgloon, Broekom, Diets-Heur, Gors-Opleeuw, Gotem, Groot-Loon, Hendrieken, Henis, 's Herenelderen, Hoepertingen, Jesseren, Kerniel, Koninksem, Kuttekoven, Lauw, Mal, Neerrepen, Nerem, Overrepen, Piringen, Rijkel, Riksingen, Rutten, Sluizen, Tongeren, Voort, Vreren, Widooie
Torhout|100|150|139|
Turnhout|50|80|73|
Veurne|110|170|168|Avekapelle, Booitshoeke, Bulskamp, De Moeren, Eggewaartskapelle, Houtem, Steenkerke, Vinkem, Wulveringem, Zoutenaaie
Vleteren|110|170|173|Oostvleteren, Westvleteren, Woesten
Voeren|90|150|124|Moelingen, Remersdaal, 's-Gravenvoeren, Sint-Martens-Voeren, Sint-Pieters-Voeren, Teuven
Vorselaar|45|70|61|
Vosselaar|50|70|69|
Waasmunster|40|65|57|
Waregem|75|120|113|Beveren, Desselgem, Sint-Eloois-Vijve
Wellen|75|110|116|Berlingen, Herten, Ulbeek
Wervik|100|150|145|Geluwe
Westerlo|50|75|74|Oevel, Tongerlo, Zoerle-Parwijs
Wetteren|60|110|66|Massemen, Westrem
Wevelgem|85|140|133|Gullegem, Moorsele
Wichelen|55|90|43|Schellebelle, Serskamp
Wielsbeke|80|130|118|Ooigem, Sint-Baafs-Vijve
Wijnegem|35|60|38|
Wingene|90|140|124|Ruiselede, Zwevezele
Wommelgem|35|60|38|
Wortegem-Petegem|80|120|117|Elsegem, Moregem, Ooike, Petegem-aan-de-Schelde, Wortegem
Wuustwezel|45|70|62|Loenhout
Zandhoven|35|55|48|Massenhoven, Pulderbos, Pulle, Viersel
Zedelgem|90|140|131|Aartrijke, Loppem, Veldegem
Zele|50|80|64|
Zelzate|50|80|75|
Zoersel|40|65|56|Halle, Sint-Antonius
Zonhoven|70|110|81|
Zonnebeke|100|150|153|Beselare, Geluveld, Passendale, Zandvoorde
Zottegem|60|100|67|Elene, Erwetegem, Godveerdegem, Grotenberge, Leeuwergem, Sint-Goriks-Oudenhove, Strijpen, Velzeke-Ruddershove, Oombergen, Sint-Maria-Oudenhove
Zoutleeuw|60|100|66|Budingen, Dormaal, Halle-Booienhoven, Helen-Bos
Zuienkerke|85|130|129|Meetkerke, Houtave, Nieuwmunster
Zulte|75|120|110|Olsene, Machelen
Zutendaal|80|120|98|
Zwalm|70|120|71|Beerlegem, Dikkele, Hundelgem, Meilegem, Munkzwalm, Nederzwalm-Hermelgem, Paulatem, Roborst, Rozebeke, Sint-Blasius-Boekel, Sint-Denijs-Boekel, Sint-Maria-Latem
Zwevegem|85|130|125|Heestert, Moen, Otegem, Sint-Denijs`;

const norm=s=>String(s).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g,'').replace(/[^a-z]/g,'');
const INDEX=new Map(); const opties=[];
function voegToe(e){const k=norm(e.naam); if(!INDEX.has(k)) INDEX.set(k,[]); INDEX.get(k).push(e); opties.push(e.naam===e.hoofd?e.naam:`${e.naam} (${e.hoofd})`);}
for(const z of [1,2,3]) for(const [hoofd,deel] of ZONELIJST[z]){ voegToe({zone:z,naam:hoofd,hoofd}); for(const d of deel) voegToe({zone:z,naam:d,hoofd}); }
for(const lijn of REIS.split('\n')){ const [hoofd,lo,hi,km,deel]=lijn.split('|'); const b={zone:'buiten',hoofd,hi:+hi};
  voegToe({...b,naam:hoofd}); for(const d of (deel||'').split(', ').map(x=>x.trim()).filter(x=>x&&x!==hoofd)) voegToe({...b,naam:d}); }
$('gemeenten').innerHTML=[...new Set(opties)].sort((a,b)=>a.localeCompare(b,'nl')).map(o=>`<option value="${o.replace(/"/g,'&quot;')}"></option>`).join('');
function zoek(v){ const m=v.match(/^(.*?)\s*\((.*)\)\s*$/); let k=INDEX.get(norm(m?m[1]:v))||[];
  if(m) k=k.filter(e=>norm(e.hoofd)===norm(m[2])); if(k.length>1){const h=k.filter(e=>e.naam===e.hoofd); if(h.length===1) k=h;} return k; }
function verplaatsing(){
  const v=$('gemeente').value.trim();
  if(!v) return {status:'leeg'};
  const k=zoek(v);
  if(k.length>1) return {status:'dubbel',opties:k.map(e=>e.hoofd)};
  if(k.length===0) return {status:'onbekend'};
  const e=k[0];
  const kost= e.zone==='buiten' ? Math.max(65,Math.round(120*e.hi/60)) : AANRIJ[e.zone];
  return {status:'ok',kost,plaats:e,lez:LEZ.includes(e.hoofd)};
}
function m2Waarde(){ return parseFloat(String($('m2').value).replace(',','.')); }
/* ---------- Kortingscode (controle op de server: korting.php) ---------- */
let korting=null; // {code,type:'procent'|'bedrag',waarde,label}
function kortingBedrag(excl){ if(!korting) return 0; const k= korting.type==='procent' ? excl*korting.waarde/100 : korting.waarde; return Math.min(excl, Math.round(k*100)/100); }
async function kortingToepassen(){
  const code=$('kcode').value.trim().toUpperCase(); const msg=$('kcode-msg'); msg.className='kcode-msg';
  if(!code){ korting=null; msg.textContent=''; update(); return; }
  $('kcode-btn').disabled=true; msg.textContent='Code controleren…';
  try{
    const r=await fetch('korting.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({code})});
    const j=await r.json();
    if(j.ok){ korting={code,type:j.type,waarde:j.waarde,label:j.label}; msg.classList.add('ok'); msg.textContent=`Code ${code} toegepast: ${j.label}.`; }
    else { korting=null; msg.classList.add('err'); msg.textContent=j.fout||'Deze code is niet geldig.'; }
  }catch(e){ korting=null; msg.classList.add('err'); msg.textContent='De code kon niet gecontroleerd worden. Probeer het later opnieuw.'; }
  $('kcode-btn').disabled=false; update();
}
$('kcode-btn').addEventListener('click',kortingToepassen);
$('kcode').addEventListener('keydown',e=>{ if(e.key==='Enter'){ e.preventDefault(); kortingToepassen(); } });
$('kcode').addEventListener('input',()=>{ if(korting){ korting=null; $('kcode-msg').textContent=''; $('kcode-msg').className='kcode-msg'; update(); } });

/* ---------- Foto's (worden in de browser verkleind tot max. 1600 px) ---------- */
const MAX_FOTOS=6; let fotos=[]; // {naam, data (base64 jpeg), url}
async function verklein(file){
  const bm=await createImageBitmap(file); const s=Math.min(1,1600/Math.max(bm.width,bm.height));
  const c=document.createElement('canvas'); c.width=Math.round(bm.width*s); c.height=Math.round(bm.height*s);
  c.getContext('2d').drawImage(bm,0,0,c.width,c.height);
  const url=c.toDataURL('image/jpeg',0.8); return {naam:file.name.replace(/\.[^.]+$/,'')+'.jpg', data:url.split(',')[1], url};
}
function toonFotos(){
  const ul=$('fotolijst'); ul.replaceChildren();
  fotos.forEach((f,i)=>{ const li=document.createElement('li'); const img=document.createElement('img'); img.src=f.url; img.alt='Foto '+(i+1);
    const b=document.createElement('button'); b.type='button'; b.className='fotoweg'; b.setAttribute('aria-label','Foto '+(i+1)+' verwijderen'); b.textContent='×';
    b.addEventListener('click',()=>{ fotos.splice(i,1); toonFotos(); }); li.append(img,b); ul.append(li); });
}
$('fotos').addEventListener('change',async e=>{
  const files=[...e.target.files].filter(f=>f.type.startsWith('image/')); $('e-fotos').textContent='';
  for(const f of files){ if(fotos.length>=MAX_FOTOS){ $('e-fotos').textContent=`Je kan maximaal ${MAX_FOTOS} foto's meesturen.`; break; }
    try{ fotos.push(await verklein(f)); }catch(err){ $('e-fotos').textContent='Een van de foto\'s kon niet gelezen worden. Probeer een JPG- of PNG-bestand.'; } }
  e.target.value=''; toonFotos();
});

function update(){
  const m2=m2Waarde(); const rein=berekenPrijs(m2); const vp=verplaatsing(); const zi=$('zinfo'); zi.classList.remove('warn');
  if(vp.status==='leeg') zi.textContent='De verplaatsingskost hangt af van je gemeente.';
  else if(vp.status==='dubbel'){ zi.classList.add('warn'); zi.textContent=`Deze naam bestaat in meerdere gemeenten (${vp.opties.join(', ')}). Kies de juiste uit de lijst.`; }
  else if(vp.status==='onbekend'){ zi.classList.add('warn'); zi.textContent='We vinden deze gemeente niet in onze lijst. Vraag gerust een offerte aan: we berekenen de verplaatsing dan apart.'; }
  else zi.textContent= vp.kost===0 ? `${vp.plaats.naam}: geen verplaatsingskost.` : `${vp.plaats.naam}: verplaatsingskost ${eur(vp.kost)} excl. btw.`+(vp.lez?' In de lage-emissiezone komt er een kleine LEZ-toeslag bij.':'');
  if(!rein){ $('pIncl').textContent='€ 0'; $('pSub').textContent='vul een oppervlakte in'; $('rRein').textContent='–'; $('rExcl').textContent='–'; $('rRit').textContent='–'; return; }
  const rit= vp.status==='ok'?vp.kost:0;
  const kb=kortingBedrag(rein+rit);
  const excl=rein+rit-kb;
  $('rKortingRij').hidden=!kb; if(kb){ $('rKortingLabel').textContent=`Korting (${korting.code})`; $('rKorting').textContent='– '+eur2(kb).replace(/,00$/,''); }
  $('pIncl').textContent=eur2(excl*1.21).replace(/,00$/,'');
  $('pSub').textContent=`voor ${m2.toLocaleString('nl-BE')} m²`+(vp.status==='ok'?` in ${vp.plaats.naam}`:'');
  $('rRein').textContent=eur(rein);
  $('rRit').textContent= vp.status==='ok' ? (vp.kost===0?'gratis':eur(vp.kost)) : vp.status==='onbekend' ? 'op aanvraag' : 'vul je gemeente in';
  $('rExcl').textContent=eur2(excl).replace(/,00$/,'');
}
$('m2').addEventListener('input',update);
$('gemeente').addEventListener('input',update);
$('chips').addEventListener('click',e=>{const b=e.target.closest('button'); if(!b) return; $('m2').value=b.dataset.v; update(); $('m2').focus();});
const gekozenTypes=()=>[...document.querySelectorAll('input[name=type]:checked')].map(i=>i.value==='Andere'?'Andere: '+($('andere-tekst').value.trim()||'?'):i.value);
$('type-andere').addEventListener('change',e=>{ $('andere-wrap').hidden=!e.target.checked; if(e.target.checked) $('andere-tekst').focus(); });
const CHECKS={
  types:()=>!gekozenTypes().length?'Duid minstens één type verharding aan.':($('type-andere').checked&&!$('andere-tekst').value.trim()?'Vul in welke andere verharding je hebt.':''),
  m2:()=>berekenPrijs(m2Waarde())?'':'Vul een oppervlakte van minstens 1 m² in.',
  naam:()=>$('naam').value.trim().length>1?'':'Vul je naam in.',
  email:()=>/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test($('email').value.trim())?'':'Vul een geldig e-mailadres in, bv. naam@voorbeeld.be.',
  tel:()=>$('tel').value.replace(/[^\d+]/g,'').replace(/^\+32/,'0').length>=9?'':'Vul een telefoonnummer in waarop we je kunnen bereiken.',
  straat:()=>/\d/.test($('straat').value)&&$('straat').value.trim().length>3?'':'Vul je straat en huisnummer in.',
  postcode:()=>/^\d{4}$/.test($('postcode').value.trim())?'':'Een Belgische postcode heeft 4 cijfers.',
  gemeente:()=>$('gemeente').value.trim()?'':'Vul je gemeente in.',
  ok:()=>$('ok').checked?'':'Vink dit aan, zodat we je mogen contacteren.'
};
function check(k){ const msg=CHECKS[k](); $('e-'+k).textContent=msg; const el=$(k); if(el.tagName==='INPUT'&&el.type!=='checkbox') el.setAttribute('aria-invalid',msg?'true':'false'); return !msg; }
$('types').addEventListener('change',()=>{ if($('e-types').textContent) check('types'); });
Object.keys(CHECKS).filter(k=>k!=='types').forEach(k=>$(k).addEventListener('blur',()=>{ if($('e-'+k).textContent) check(k); }));
$('form').addEventListener('submit',async e=>{
  e.preventDefault();
  const fout=Object.keys(CHECKS).filter(k=>!check(k));
  if(fout.length){ (fout[0]==='types'?document.querySelector('input[name=type]'):$(fout[0])).focus(); return; }
  const m2=m2Waarde(), rein=berekenPrijs(m2), vp=verplaatsing();
  const kb=kortingBedrag(rein+(vp.status==='ok'?vp.kost:0));
  const excl=rein+(vp.status==='ok'?vp.kost:0)-kb;
  const aanvraag={naam:$('naam').value.trim(),email:$('email').value.trim(),tel:$('tel').value.trim(),
    types:gekozenTypes(), nieuwsbrief:$('nieuwsbrief').checked, adres:`${$('straat').value.trim()}, ${$('postcode').value.trim()} ${$('gemeente').value.trim()}`,
    m2, reiniging:rein, verplaatsing: vp.status==='ok'?vp.kost:null, kortingscode: korting?korting.code:'', totaalExcl:excl, totaalIncl:Math.round(excl*121)/100, fotos: fotos.map(f=>({naam:f.naam,data:f.data})), opmerking:$('opm').value.trim(), zones: zones.map(z=>({m2:Math.round(geoOpp(z.punten)*10)/10, punten:z.punten.map(p=>[+p.lat.toFixed(7),+p.lng.toFixed(7)])}))};
  aanvraag.type='offerte'; aanvraag.website=$('hp-website').value; aanvraag.t=START_TIJD;
  const knop=document.querySelector('#form button[type=submit]'); knop.disabled=true; knop.textContent='Bezig met versturen…';
  $('sendmsg').hidden=true;
  let gelukt=false;
  try{
    const r=await fetch('verstuur.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(aanvraag)});
    const j=await r.json().catch(()=>({}));
    gelukt=r.ok&&j.ok;
  }catch(e){}
  knop.disabled=false; knop.textContent='Offerte aanvragen';
  if(!gelukt){ $('sendmsg').hidden=false; $('sendmsg').textContent='Je aanvraag kon niet verstuurd worden. Probeer het opnieuw, of mail ons op info@ssprojects.be of bel 0468 43 00 53.'; return; }
  $('d-naam').textContent=aanvraag.naam.split(' ')[0];
  const rijen=[['Verharding',aanvraag.types.join(', ')],['Oppervlakte',`${m2.toLocaleString('nl-BE')} m²`],['Adres',aanvraag.adres],['E-mail',aanvraag.email],['Telefoon',aanvraag.tel],
    ['Prijs',`${eur2(aanvraag.totaalIncl)} incl. btw`+(vp.status==='ok'?'':' + verplaatsing')]];
  if(aanvraag.zones.length) rijen.push(['Op kaart',`${aanvraag.zones.length} zone${aanvraag.zones.length>1?'s':''} ingetekend`]);
  if(korting) rijen.push(['Kortingscode',`${korting.code} (${korting.label})`]);
  if(fotos.length) rijen.push(["Foto's",`${fotos.length} meegestuurd`]);
  if(aanvraag.opmerking) rijen.push(['Opmerking',aanvraag.opmerking]);
  $('d-list').replaceChildren(...rijen.flatMap(([a,b])=>{const dt=document.createElement('dt');dt.textContent=a;const dd=document.createElement('dd');dd.textContent=b;return [dt,dd];}));
  document.querySelectorAll('#form .panel:not(.done)').forEach(p=>p.hidden=true); document.querySelector('.calc-side').hidden=true;
  $('done').hidden=false; $('done').scrollIntoView({behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth',block:'start'});
});
$('nieuw').addEventListener('click',()=>{ stopTekenen(); [...zones].forEach(z=>zoneWeg(z.id)); zetModus(false); $('form').reset(); $('andere-wrap').hidden=true; fotos=[]; toonFotos(); korting=null; $('kcode-msg').textContent=''; $('m2').value=40; document.querySelectorAll('#form .panel').forEach(p=>p.hidden=false); document.querySelector('.calc-side').hidden=false; $('done').hidden=true; Object.keys(CHECKS).forEach(k=>{$('e-'+k).textContent='';}); update(); $('m2').focus(); });
/* ---------- Oppervlakte intekenen op luchtfoto ----------
   Luchtfoto: Digitaal Vlaanderen, Orthofotomozaïek middenschalig zomeropnamen 2024 (WMTS, Vlaamse Open Data licentie).
   Daaronder OpenStreetMap als reserve buiten Vlaanderen. Adres zoeken: geolocatiedienst Digitaal Vlaanderen,
   met OpenStreetMap Nominatim als reserve. Werkt pas volledig op de echte website. */
const ORTHO_URL='https://geo.api.vlaanderen.be/OMZ/wmts/1.0.0/omzrgb24vl/default/GoogleMapsVL/{z}/{y}/{x}.png';
const START=[50.9850,4.4570]; // Beekveldstraat, Zemst
let kaart=null, zones=[], bezig=null, tegelFout=0, tegelOk=0, zoneTeller=0;
function geoOpp(ll){ // geodetische oppervlakte in m² (zelfde formule als Leaflet.draw)
  const R=6378137, d=Math.PI/180; let a=0;
  for(let i=0;i<ll.length;i++){const p1=ll[i],p2=ll[(i+1)%ll.length]; a+=(p2.lng-p1.lng)*d*(2+Math.sin(p1.lat*d)+Math.sin(p2.lat*d));}
  return Math.abs(a*R*R/2);
}
const m2fmt=n=>n.toLocaleString('nl-BE',{maximumFractionDigits:1})+' m²';
function cssVar(n){return getComputedStyle(document.documentElement).getPropertyValue(n).trim()||'#38761d';}
function vtxIcon(first){return L.divIcon({className:'vtx'+(first?' first':''),iconSize:[14,14]});}
function maakKaart(){
  if(kaart) { kaart.invalidateSize(); return true; }
  if(!window.L){ $('maphint').classList.add('warn'); $('maphint').textContent='De kaart kon niet geladen worden. Vul de oppervlakte zelf in, of vermeld in de opmerking dat je hulp wil bij het opmeten.'; return false; }
  kaart=L.map('map',{zoomControl:true,maxZoom:21,doubleClickZoom:true}).setView(START,18);
  kaart.attributionControl.setPrefix('<a href="https://leafletjs.com" target="_blank" rel="noopener">Leaflet</a>');
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:21,maxNativeZoom:19,attribution:'© OpenStreetMap'}).addTo(kaart);
  const ortho=L.tileLayer(ORTHO_URL,{maxZoom:21,maxNativeZoom:21,attribution:'Luchtfoto © Digitaal Vlaanderen'}).addTo(kaart);
  ortho.on('tileerror',()=>{tegelFout++; if(tegelFout>6&&tegelOk===0){$('maphint').classList.add('warn'); $('maphint').textContent='De luchtfoto laadt hier niet (in deze voorbeeldpagina is dat normaal). Je kan wel al tekenen om de werking te testen.';}});
  ortho.on('tileload',()=>{tegelOk++;});
  kaart.on('click',e=>{ if(bezig) puntErbij(e.latlng); });
  return true;
}
async function zoekAdres(q){
  q=q.trim(); if(!q||!kaart) return;
  $('maphint').classList.remove('warn'); $('maphint').textContent='Adres zoeken…';
  const pogingen=[
    async()=>{const r=await fetch('https://geo.api.vlaanderen.be/geolocation/v4/Location?c=1&q='+encodeURIComponent(q)); const j=await r.json(); const l=j.LocationResult&&j.LocationResult[0]; return l?[l.Location.Lat_WGS84,l.Location.Lon_WGS84]:null;},
    async()=>{const r=await fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=be&q='+encodeURIComponent(q)); const j=await r.json(); return j[0]?[+j[0].lat,+j[0].lon]:null;}
  ];
  for(const p of pogingen){ try{ const ll=await p(); if(ll){ kaart.setView(ll,20); $('maphint').textContent='Gevonden. Zoom eventueel nog in of uit, en klik dan op "Zone tekenen".'; return; } }catch(e){} }
  $('maphint').classList.add('warn'); $('maphint').textContent='Adres niet gevonden. Versleep de kaart en zoom zelf in tot je je woning ziet.';
}
function knoppen(){
  const t=!!bezig;
  $('tekenbtn').hidden=t; $('klaarbtn').hidden=!t; $('undobtn').hidden=!t; $('stopbtn').hidden=!t;
  $('klaarbtn').disabled=!(t&&bezig.punten.length>=3); $('undobtn').disabled=!(t&&bezig.punten.length);
  $('map').classList.toggle('drawing',t);
  if(kaart){ t?kaart.doubleClickZoom.disable():kaart.doubleClickZoom.enable(); }
}
function startTekenen(){
  if(!kaart) return;
  const kleur=cssVar('--green-ink');
  bezig={punten:[],markers:[],lijn:L.polyline([],{color:kleur,weight:3,dashArray:'6 6'}).addTo(kaart)};
  $('maphint').classList.remove('warn');
  $('maphint').textContent='Klik de hoeken van je verharding aan. Klik op het eerste punt of op "Zone afsluiten" om af te werken.';
  knoppen();
}
function puntErbij(ll){
  const i=bezig.punten.length;
  bezig.punten.push(ll);
  const m=L.marker(ll,{icon:vtxIcon(i===0),keyboard:false}).addTo(kaart);
  if(i===0) m.on('click',ev=>{ L.DomEvent.stopPropagation(ev); if(bezig&&bezig.punten.length>=3) afsluiten(); });
  bezig.markers.push(m); bezig.lijn.setLatLngs(bezig.punten); knoppen();
}
function laatsteWeg(){ if(!bezig||!bezig.punten.length) return; bezig.punten.pop(); kaart.removeLayer(bezig.markers.pop()); bezig.lijn.setLatLngs(bezig.punten); knoppen(); }
function stopTekenen(){ if(!bezig) return; bezig.markers.forEach(m=>kaart.removeLayer(m)); kaart.removeLayer(bezig.lijn); bezig=null; knoppen(); }
function afsluiten(){
  const kleur=cssVar('--green-ink'); const punten=bezig.punten.slice();
  bezig.markers.forEach(m=>kaart.removeLayer(m)); kaart.removeLayer(bezig.lijn); bezig=null;
  const z={id:++zoneTeller,punten};
  z.vlak=L.polygon(punten,{color:kleur,weight:2.5,fillColor:kleur,fillOpacity:.28}).addTo(kaart);
  z.vlak.bindTooltip('',{permanent:true,direction:'center',className:'zlabel'});
  z.markers=punten.map((p,i)=>{ const m=L.marker(p,{icon:vtxIcon(false),draggable:true,keyboard:false}).addTo(kaart);
    m.on('drag',ev=>{ z.punten[i]=ev.target.getLatLng(); z.vlak.setLatLngs(z.punten); herbereken(); }); return m; });
  zones.push(z); knoppen(); herbereken();
  $('maphint').classList.remove('warn');
  $('maphint').textContent='Zone toegevoegd. Versleep de bolletjes om bij te stellen, of teken een extra zone (bv. oprit en terras apart).';
}
function zoneWeg(id){ const i=zones.findIndex(z=>z.id===id); if(i<0) return; const z=zones[i]; kaart.removeLayer(z.vlak); z.markers.forEach(m=>kaart.removeLayer(m)); zones.splice(i,1); herbereken(); }
function totaalKaart(){ return zones.reduce((s,z)=>s+geoOpp(z.punten),0); }
function herbereken(){
  const ul=$('zonelist'); ul.replaceChildren();
  zones.forEach((z,i)=>{ const opp=geoOpp(z.punten); z.vlak.setTooltipContent(m2fmt(opp));
    const li=document.createElement('li'); const s=document.createElement('span'); s.textContent=`Zone ${i+1}: ${m2fmt(opp)}`;
    const b=document.createElement('button'); b.type='button'; b.className='del'; b.textContent='Verwijderen'; b.addEventListener('click',()=>zoneWeg(z.id));
    li.append(s,b); ul.append(li); });
  if(zones.length){
    const tot=totaalKaart(); const li=document.createElement('li'); li.className='maptotal'; li.textContent=`Totaal: ${m2fmt(tot)} (ingevuld bij oppervlakte)`; ul.append(li);
    $('m2').value=Math.max(1,Math.round(tot*2)/2);
  }
  update();
}
function zetModus(kaartModus){
  $('mode-zelf').setAttribute('aria-pressed',String(!kaartModus)); $('mode-kaart').setAttribute('aria-pressed',String(kaartModus));
  $('mapbox').hidden=!kaartModus; $('m2hint').hidden=kaartModus; $('chips').hidden=kaartModus;
  if(kaartModus && maakKaart()){
    const adres=[$('straat').value.trim(),[$('postcode').value.trim(),$('gemeente').value.trim()].filter(Boolean).join(' ')].filter(Boolean).join(', ');
    if(adres && !$('zoekadres').value){ $('zoekadres').value=adres; zoekAdres(adres); }
  }
}
$('mode-zelf').addEventListener('click',()=>zetModus(false));
$('mode-kaart').addEventListener('click',()=>zetModus(true));
$('zoekbtn').addEventListener('click',()=>zoekAdres($('zoekadres').value));
$('zoekadres').addEventListener('keydown',e=>{ if(e.key==='Enter'){ e.preventDefault(); zoekAdres($('zoekadres').value); } });
$('tekenbtn').addEventListener('click',startTekenen);
$('klaarbtn').addEventListener('click',()=>{ if(bezig&&bezig.punten.length>=3) afsluiten(); });
$('undobtn').addEventListener('click',laatsteWeg);
$('stopbtn').addEventListener('click',stopTekenen);

// gemeente voorinvullen vanaf een gemeentepagina (?gemeente=...)
try{ const qg=new URLSearchParams(location.search).get('gemeente'); if(qg&&!$('gemeente').value) $('gemeente').value=qg.slice(0,80); }catch(e){}
update();
