let productList = [
    { produit: "a", prix: 30, views: 1200 },
    { produit: "b", prix: 20, views: 2500 },
    { produit: "c", prix: 15, views: 1800 },
    { produit: "d", prix: 40, views: 3000 },
    { produit: "e", prix: 10, views: 900 },
    { produit: "f", prix: 25, views: 2000 }
];

let Budget = 100;


// ===============================
// ETAPE 1 : TRI PAR VIEWS
// ===============================

for (let i = 0; i < productList.length; i++) {

    for (let j = i + 1; j < productList.length; j++) {

        if (productList[j].views < productList[i].views) {

            let temp = productList[i];

            productList[i] = productList[j];

            productList[j] = temp;
        }
    }
}

console.log(productList);


// ===============================
// ETAPE 2 : FILTRE views >= 1500
// ===============================

let produitsPopulaires = [];

for (let i = 0; i < productList.length; i++) {

    if (productList[i].views >= 1500) {

        produitsPopulaires.push(productList[i]);
    }
}

console.log(produitsPopulaires);


// ===============================
// ETAPE 3 : TRI PAR PRIX
// ===============================

for (let i = 0; i < produitsPopulaires.length; i++) {

    for (let j = i + 1; j < produitsPopulaires.length; j++) {

        if (produitsPopulaires[j].prix < produitsPopulaires[i].prix) {

            let temp = produitsPopulaires[i];

            produitsPopulaires[i] = produitsPopulaires[j];

            produitsPopulaires[j] = temp;
        }
    }
}

console.log(produitsPopulaires);


// ===============================
// ETAPE 4 : ACHAT AVEC BUDGET
// ===============================

let itemCounter = 0;

let totalPrix = 0;

for (let i = 0; i < produitsPopulaires.length; i++) {

    if (produitsPopulaires[i].prix <= Budget) {

        Budget = Budget - produitsPopulaires[i].prix;

        totalPrix = totalPrix + produitsPopulaires[i].prix;

        itemCounter++;
    }
}


// ===============================
// RESULTAT
// ===============================

let resultat = [
    {
        totalPrix: totalPrix,
        totalItems: itemCounter,
        leftChange: Budget
    }
];

console.log(resultat);