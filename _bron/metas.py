# Meta-beschrijvingen per pagina (max. ±155 tekens: zoekwoord vooraan, voordeel, call to action)
META = {
'index.html':"Terras of oprit ecologisch laten reinigen? Zonder hogedruk of chemie, zonder schade aan voegen of stenen. Regio Zemst en Mechelen. Bereken direct je prijs.",
'prijsberekening.html':"Wat kost het reinigen van je terras of oprit? Vul de m² in of teken je verharding op de luchtfoto en zie meteen je prijs incl. btw. Vraag je offerte aan.",
'werkwijze.html':"Zo reinigen we je terras of oprit: met borstels en water, zonder hogedruk of chemie. Wat moet je voorzien? Lees de antwoorden en bereken meteen je prijs.",
'over-ons.html':"Super Schoon is de reinigingsdienst van tuinaannemer S&S Projects uit Zemst, met 10+ jaar ervaring in terrassen en opritten. Maak kennis met ons team.",
'contact.html':"Vragen over het reinigen van je terras of oprit? Bel 0468 43 00 53, mail info@ssprojects.be of stuur ons meteen een bericht. Super Schoon, Zemst.",
'sitemap.html':"Super Schoon reinigt terrassen en opritten in Zemst, Mechelen, Vilvoorde, Leuven, Brussel, Antwerpen en omstreken. Zoek je gemeente en bekijk je prijs.",
'algemene-voorwaarden.html':"Algemene voorwaarden van Super Schoon (S&S Projects BV) voor het reinigen van terrassen, opritten en paden: offerte, prijs, uitvoering, aansprakelijkheid.",
'privacy.html':"Hoe Super Schoon (S&S Projects BV) je gegevens gebruikt bij een prijsaanvraag of bericht, hoe lang we ze bewaren en welke rechten je hebt onder de GDPR.",
}

for _k, _v in META.items(): assert len(_v) <= 155, (_k, len(_v))
