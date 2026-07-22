export function numeroATexto(num) {
  const unidades = ["", "uno", "dos", "tres", "cuatro", "cinco", "seis", "siete", "ocho", "nueve"];
  const decenas = ["", "diez", "veinte", "treinta", "cuarenta", "cincuenta", "sesenta", "setenta", "ochenta", "noventa"];
  const centenas = ["", "cien", "doscientos", "trescientos", "cuatrocientos", "quinientos", "seiscientos", "setecientos", "ochocientos", "novecientos"];

  if (num === 0) return "cero";
  const miles = Math.floor(num / 1000);
  const resto = num % 1000;

  const toWords = (n) => {
    const c = Math.floor(n / 100);
    const d = Math.floor((n % 100) / 10);
    const u = n % 10;
    let texto = "";
    if (c > 0) texto += centenas[c] + " ";
    if (d > 0 || u > 0) {
      if (d === 1 && u > 0) {
        const especiales = ["once", "doce", "trece", "catorce", "quince"];
        texto += especiales[u - 1] || "dieci" + unidades[u];
      } else if (d === 2 && u > 0) {
        texto += "veinti" + unidades[u];
      } else {
        texto += decenas[d];
        if (d > 2 && u > 0) texto += " y ";
        texto += unidades[u];
      }
    }
    return texto.trim();
  };

  let texto = "";
  if (miles > 0) texto += (miles === 1 ? "mil" : toWords(miles) + " mil ");
  texto += toWords(resto);
  return texto.trim();
}
