<template>
    <div class="w-full h-full px-4 my-2 overflow-y-auto">
        <div class="grid grid-cols-12 gap-4 w-full items-start auto-rows-auto px-2">
            <ThemeBuilder />

            <div class="col-span-12 w-full flex gap-4">
                <button class="bg-blue-500 text-white px-4 py-2 rounded cursor-pointer" @click="downloadInvoice()">
                    <span>Download invoice</span>
                </button>

                <button class="bg-red-500 text-white px-4 py-2 rounded cursor-pointer" @click="openModal()">
                    <span>open modal</span>
                </button>

                <button class="bg-blue-800 text-white px-4 py-2 rounded cursor-pointer" @click="clearSession()">
                    <span>Clear session</span>
                </button>

                <AdvancedDropdown
                    :align="'right'"
                    customClass="!bg-[var(--color-background)]"
                    customDropdownClass="w-40"
                    position="bottom"
                >
                    <!-- Custom trigger -->
                    <template #trigger="{ onToggleDropdown }">
                        <div
                            class="mx-2 flex items-center justify-center rounded cursor-pointer bg-[var(--color-background)] hover:bg-[var(--color-highlight-dark)] transition-colors duration-200 ease-in px-2 py-2"
                            @click="onToggleDropdown"
                        >
                            <Icon class="w-6 h-6 text-white rotate-90" icon="material-symbols:chevron-right-rounded" />
                        </div>
                    </template>

                    <!-- Dropdown content -->
                    <template #dropdown-content>
                        <ul class="text-sm text-gray-700">
                            <li class="px-4 py-2 hover:bg-gray-100 cursor-pointer">Profile</li>
                            <li class="px-4 py-2 hover:bg-gray-100 cursor-pointer">Settings</li>
                            <li class="px-4 py-2 hover:bg-gray-100 cursor-pointer">Logout</li>
                        </ul>
                    </template>
                </AdvancedDropdown>
                <div class="flex">
                    <button class="bg-[var(--color-primary)] text-white px-4 py-2 rounded mr-2">Primary</button>
                    <button class="bg-[var(--color-success)] text-white px-4 py-2 rounded mr-2" @click="showSuccess">
                        Success
                    </button>
                    <button class="bg-[var(--color-warning)] text-white px-4 py-2 rounded mr-2" @click="showWarning">
                        Warning
                    </button>
                    <button class="bg-[var(--color-error)] text-white px-4 py-2 rounded mr-2" @click="showError">
                        Error
                    </button>
                    <button class="bg-sky-100 border border-sky-500 text-sky px-4 py-2 rounded mr-2" @click="showInfo">
                        Info
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
    import { computed, defineComponent, onMounted, ref } from 'vue';
    import { useModalService } from '@/composables/useModalService';
    import { ModalActionTarget } from '@/modules/enums/support/modals/ModalActionTarget.ts';
    import { ModalActionType } from '@/modules/enums/support/modals/ModalActionType.ts';
    import { ModalResultType } from '@/modules/enums/support/modals/ModalResultType.ts';
    import { ModalType } from '@/modules/enums/support/modals/ModalType.ts';
    import type { ModalResult } from '@/modules/types/support/modals/ModalResult.ts';
    import EntityModal from '@/components/modals/content-modals/EntityModal.vue';
    import AdvancedDropdown from '@/components/inputs/AdvancedDropdown.vue';
    import { Icon } from '@iconify/vue';
    import http from '@/utils/http';
    import type { EntityModalDataResult } from '@/modules/types/support/inputs/EntityModalDataResult.ts';
    import { AxiosError } from 'axios';
    import { useToastService } from '@/composables/useToastService';
    import User from '@/models/auth/User.ts';
    import ThemeBuilder from '@/components/development/ThemeBuilder.vue';

    defineComponent({
        name: 'DevelopmentPage',
    });

    const toastService = useToastService();
    const isTestingReservation = ref(false);
    const reservationResponse = ref<unknown>(null);
    const reservationError = ref<string | null>(null);

    // Hardcoded dummy data for testing
    const dummyBookingContext = {
        location: {
            id: 1,
            created_at: '2025-08-30T14:30:32+00:00',
            updated_at: '2025-08-30T14:30:32+00:00',
            active: true,
            address: '4021 GG Maurik',
            city: 'Maurik',
            country: 'NL',
            image: {
                thumb_url: 'http://localhost/storage/27/conversions/bbq_donut_boat_evening-thumb.webp',
                url: 'http://localhost/storage/27/bbq_donut_boat_evening.jpeg',
            },
            latitude: 51.977906,
            longitude: 5.427725,
            name: 'Eiland van Maurik',
            slug: 'eiland-van-maurik',
            timezone: 'Europe/Amsterdam',
            zip_code: '4021GG',
        },
        activity_id: 2,
        date: '2026-01-22',
        timeslot_id: 3,
        guest_amount: 4,
        customer: {
            phone: '+31641263550',
            id: 63,
        },
        arrangement_quantities: {
            2: 1,
            4: 1,
            5: 1,
            6: 1,
        },
        drink_quantities: {
            16: 5,
            23: 7,
        },
        extra_drink_quantities: {
            16: 2,
        },
        extra_dish_quantities: null,
        pricing_tier: {
            id: 4,
            created_at: '2025-10-06T20:33:42+00:00',
            updated_at: '2025-10-06T20:33:42+00:00',
            guests: 4,
            price: '80.00',
            pricing_table_id: 1,
        },
        payment_method: null,
        hold_id: 13,
    };


    // const { fetch } = useSettingHelper();

    const modalService = useModalService();

    const downloadInvoice = async () => {
        const response = await http.post(
            'invoices/download',
            {
                invoice_number: '100004',
                invoice_lines: [
                    {
                        description: 'Development application BBQ-dubai',
                        hours: 250,
                        hourly_rate: 20,
                    },
                ],
                charge_dutch_vat: false,
            },
            {
                responseType: 'blob',
            },
        );

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'invoice_100001.pdf');
        document.body.appendChild(link);
        link.click();

        // Clean up
        document.body.removeChild(link);
    };

    const openModal = () => {
        modalService.open({
            title: 'Entity modal',
            type: ModalType.SLIDE_IN_RIGHT,
            component: EntityModal,
            props: {
                entity: 'tyres',
                model: User,
            },
            actions: [
                {
                    type: ModalResultType.DISMISS,
                    name: 'GENERAL_CANCEL',
                    variant: ModalActionType.DISMISS,
                    target: ModalActionTarget.CANCEL,
                },
                {
                    type: ModalResultType.ACCEPT,
                    name: 'GENERAL_SAVE',
                    variant: ModalActionType.PRIMARY,
                    target: ModalActionTarget.SAVE,
                },
            ],
            onAccept(result: ModalResult | null) {
                if (!result) {
                    return;
                }

                if (result.target === ModalActionTarget.SAVE) {
                    const data = result.data as EntityModalDataResult;
                    const modelInstance = new data.model();

                    if (data.record.id) {
                        //todo: handle update
                    } else {
                        modelInstance.create(data.record);
                    }
                }
            },
        });
    };

    function clearSession(): void {
        http.post('/logout', {}, { baseURL: import.meta.env.VITE_API_URL + '/api' })
            .then(() => {
                localStorage.removeItem('token');
            })
            .catch((error: AxiosError) => {
                console.error('Logout failed:', error);
            });
    }

    function showInfo(): void {
        toastService.info('This is an info message for testing purposes.');
    }

    function showError(): void {
        toastService.error('This is an error message for testing purposes.');
    }

    function showWarning(): void {
        toastService.warning('This is a warning message for testing purposes.');
    }

    function showSuccess(): void {
        toastService.success('This is a success message for testing purposes.');
    }
</script>
