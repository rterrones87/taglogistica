// Usa aleatoriedad criptográfica y garantiza las reglas del formulario de usuarios.
export function generateUserPassword() {
    const groups = ['abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', '0123456789', '@$!%*?&'];
    const alphabet = groups.join('');
    const randomIndex = (length) => {
        const values = new Uint32Array(1);
        const limit = Math.floor(0x100000000 / length) * length;
        do {
            globalThis.crypto.getRandomValues(values);
        } while (values[0] >= limit);
        return values[0] % length;
    };

    const characters = groups.map(group => group[randomIndex(group.length)]);
    while (characters.length < 15) {
        characters.push(alphabet[randomIndex(alphabet.length)]);
    }
    for (let i = characters.length - 1; i > 0; i--) {
        const j = randomIndex(i + 1);
        [characters[i], characters[j]] = [characters[j], characters[i]];
    }
    return characters.join('');
}
