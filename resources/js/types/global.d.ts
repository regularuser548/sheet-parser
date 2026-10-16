import type { Auth } from '@/types/auth';
import type { FlashToast } from '@/types/ui';
import type { ValidationReport } from '@/types/sheets';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
        flashDataType: {
            toast?: FlashToast;
            validation?: ValidationReport;
        };
    }
}
