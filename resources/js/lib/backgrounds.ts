export interface BackgroundPreset {
    id: string;
    name: string;
    value: string;
}

/**
 * Catálogo de fondos (gradientes) para la página pública. El valor se guarda
 * como override en `page.theme.tokens.bg`; al elegir uno se adaptan también el
 * color de texto y los botones (ver ThemePanel) para mantener el contraste.
 */
export const BACKGROUNDS: BackgroundPreset[] = [
    { id: 'violeta', name: 'Violeta', value: 'linear-gradient(160deg, #6d28d9 0%, #9333ea 45%, #db2777 100%)' },
    { id: 'oceano', name: 'Océano', value: 'linear-gradient(160deg, #0ea5e9 0%, #2563eb 100%)' },
    { id: 'atardecer', name: 'Atardecer', value: 'linear-gradient(160deg, #f97316 0%, #db2777 100%)' },
    { id: 'bosque', name: 'Bosque', value: 'linear-gradient(160deg, #10b981 0%, #065f46 100%)' },
    { id: 'fuego', name: 'Fuego', value: 'linear-gradient(160deg, #ef4444 0%, #b91c1c 55%, #7c2d12 100%)' },
    { id: 'menta', name: 'Menta', value: 'linear-gradient(160deg, #14b8a6 0%, #0891b2 100%)' },
    { id: 'uva', name: 'Uva', value: 'linear-gradient(160deg, #4f46e5 0%, #7c3aed 100%)' },
    { id: 'rosa', name: 'Rosa', value: 'linear-gradient(160deg, #ec4899 0%, #be123c 100%)' },
    { id: 'medianoche', name: 'Medianoche', value: 'linear-gradient(160deg, #0f172a 0%, #1e293b 55%, #334155 100%)' },
    { id: 'aurora', name: 'Aurora', value: 'linear-gradient(160deg, #6366f1 0%, #a855f7 50%, #ec4899 100%)' },
];
