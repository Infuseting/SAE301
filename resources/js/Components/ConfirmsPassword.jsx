import { useRef, useState } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import axios from 'axios';

export default function ConfirmsPassword({ title, content, button, onConfirm, children }) {
    const messages = usePage().props.translations?.messages || {};
    const finalTitle = title || messages.confirm_password || 'Confirm Password';
    const finalContent = content || messages.confirm_password_content || 'For your security, please confirm your password to continue.';
    const finalButton = button || messages.confirm_button || 'Confirm';
    const [confirmingPassword, setConfirmingPassword] = useState(false);
    const [form, setForm] = useState({
        password: '',
        error: '',
        processing: false,
    });

    const passwordInput = useRef();
    

    const startConfirmingPassword = () => {
        axios.get('/user/confirmed-password-status').then(response => {
            if (response.data.confirmed) {
                onConfirm();
            } else {
                setConfirmingPassword(true);
                setTimeout(() => passwordInput.current.focus(), 250);
            }
        });
    };

    const confirmPassword = (e) => {
        e.preventDefault();
        setForm({ ...form, processing: true });

        axios.post('/user/confirm-password', {
            password: form.password,
        }).then(() => {
            setForm({ ...form, processing: false, password: '', error: '' });
            setConfirmingPassword(false);
            onConfirm();
        }).catch(error => {
            setForm({
                ...form,
                processing: false,
                error: error.response.data.errors.password[0],
            });
            passwordInput.current.focus();
        });
    };

    const closeModal = () => {
        setConfirmingPassword(false);
        setForm({ ...form, password: '', error: '' });
    };

    return (
        <span>
            <span onClick={startConfirmingPassword}>
                {children}
            </span>

            <Modal show={confirmingPassword} onClose={closeModal}>
                <form onSubmit={confirmPassword} className="p-6">
                    <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                        {finalTitle}
                    </h2>

                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {finalContent}
                    </p>

                    <div className="mt-6">
                        <InputLabel htmlFor="password" value={finalTitle} className="sr-only" />

                        <TextInput
                            id="password"
                            type="password"
                            name="password"
                            ref={passwordInput}
                            value={form.password}
                            onChange={(e) => setForm({ ...form, password: e.target.value })}
                            className="mt-1 block w-3/4"
                            placeholder={finalTitle}
                            isFocused
                        />

                        <InputError message={form.error} className="mt-2" />
                    </div>

                    <div className="mt-6 flex justify-end">
                        <SecondaryButton onClick={closeModal}>
                            {messages.cancel || 'Cancel'}
                        </SecondaryButton>

                        <PrimaryButton className="ms-3" disabled={form.processing}>
                            {finalButton}
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>
        </span>
    );
}
