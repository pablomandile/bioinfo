import type { BlockDefinition } from '@/blocks/types';
import { Link as LinkIcon } from 'lucide-vue-next';
import LinkBlock from './LinkBlock.vue';
import LinkEditor from './LinkEditor.vue';

const definition: BlockDefinition = {
    type: 'link',
    label: 'Enlace',
    category: 'basic',
    icon: LinkIcon,
    renderer: LinkBlock,
    editor: LinkEditor,
    defaultData: () => ({ label: 'Nuevo enlace', url: '' }),
};

export default definition;
