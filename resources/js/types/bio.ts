export type BlockSize = 'sm' | 'md' | 'lg';
export type PageLayout = 'list' | 'grid';
export type ThemeMode = 'light' | 'dark';

export interface PublicBlock {
    id: string;
    type: string;
    data: Record<string, unknown>;
    size: BlockSize;
    gridColSpan: number;
    gridRowSpan: number;
    isVisible: boolean;
}

export interface PublicProfile {
    username: string;
    title: string;
    bio: string | null;
    avatarUrl: string | null;
}

export interface PublicSocial {
    platform: string;
    label: string;
    url: string;
}

export interface PublicTheme {
    mode: ThemeMode;
    cssVars: Record<string, string>;
}

export interface PublicMeta {
    title: string;
    description: string | null;
    ogImage: string | null;
    noindex: boolean;
}

export interface PublicPageProps {
    profile: PublicProfile;
    layout: PageLayout;
    blocks: PublicBlock[];
    social: PublicSocial[];
    theme: PublicTheme;
    meta: PublicMeta;
    isOwnerPreview: boolean;
}
