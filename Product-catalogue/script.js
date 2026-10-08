const products = [
  { id: 1, name: "Studio-Kopfhörer", category: "Kopfhörer", price: 59.99 },
  { id: 2, name: "Gaming-Headset", category: "Kopfhörer", price: 79.99 },
  { id: 3, name: "Mechanische Tastatur", category: "Tastaturen", price: 89.99 },
  { id: 4, name: "Kompakte Tastatur", category: "Tastaturen", price: 39.99 },
  { id: 5, name: "Kabellose Maus", category: "Mäuse", price: 24.99 },
  { id: 6, name: "Gaming-Maus", category: "Mäuse", price: 49.99 },
  { id: 7, name: "Ergonomische Maus", category: "Mäuse", price: 34.99 }
];

const productContainer = document.getElementById("products");
const searchInput = document.getElementById("search");
const categorySelect = document.getElementById("category");
const sortSelect = document.getElementById("sort");
const resultCount = document.getElementById("result-count");

const priceFormatter = new Intl.NumberFormat("de-DE", {
  style: "currency",
  currency: "EUR"
});

function renderProducts(list) {
  productContainer.replaceChildren();

  resultCount.textContent =
    list.length === 1
      ? "1 Produkt gefunden"
      : `${list.length} Produkte gefunden`;

  if (list.length === 0) {
    productContainer.textContent = "Keine passenden Produkte gefunden.";
    return;
  }

  for (const product of list) {
    const card = document.createElement("article");
    card.className = "product-card";

    const title = document.createElement("h2");
    title.textContent = product.name;

    const category = document.createElement("p");
    category.textContent = product.category;

    const price = document.createElement("p");
    price.className = "price";
    price.textContent = priceFormatter.format(product.price);

    card.append(title, category, price);
    productContainer.append(card);
  }
}

function updateProducts() {
  const searchText = searchInput.value
    .trim()
    .toLocaleLowerCase("de-DE");

  const selectedCategory = categorySelect.value;

  // Filter creates a new array, leaving the original order unchanged.
  const filteredProducts = products.filter((product) => {
    const matchesSearch = product.name
      .toLocaleLowerCase("de-DE")
      .includes(searchText);

    const matchesCategory =
      selectedCategory === "all" ||
      product.category === selectedCategory;

    return matchesSearch && matchesCategory;
  });

  // Sort the matching products before displaying them.
  if (sortSelect.value === "price-asc") {
    filteredProducts.sort((a, b) => a.price - b.price);
  } else if (sortSelect.value === "price-desc") {
    filteredProducts.sort((a, b) => b.price - a.price);
  }

  renderProducts(filteredProducts);
}

searchInput.addEventListener("input", updateProducts);
categorySelect.addEventListener("change", updateProducts);
sortSelect.addEventListener("change", updateProducts);

updateProducts();