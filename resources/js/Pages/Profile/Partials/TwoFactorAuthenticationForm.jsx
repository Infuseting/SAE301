import { useRef, useState } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import axios from 'axios';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import ConfirmsPassword from '@/Components/ConfirmsPassword';
import DangerButton from '@/Components/DangerButton';
import SetPasswordForm from './SetPasswordForm';

export default function TwoFactorAuthenticationForm({ className = '', hasPassword = true }) {
    const { auth } = usePage().props;
    const [enabling, setEnabling] = useState(false);
    const [confirming, setConfirming] = useState(false);
    const [showSetPassword, setShowSetPassword] = useState(false);
    const [enabled, setEnabled] = useState(auth.user?.two_factor_enabled);
    const [qrCode, setQrCode] = useState(null);
    const [recoveryCodes, setRecoveryCodes] = useState([]);
    const [confirmationCode, setConfirmationCode] = useState('');
    const [errors, setErrors] = useState({});

    const messages = usePage().props.translations?.messages || {};

    const enableTwoFactorAuthentication = () => {
        setEnabling(true);

        axios.post('/user/two-factor-authentication')
            .then(() => {
                Promise.all([
                    showQrCode(),
                    showRecoveryCodes(),
                ]).then(() => {
                    setConfirming(true);
                });
            })
            .catch(error => {
                console.error(error);
                setEnabling(false);
            });
    };

    const showQrCode = () => {
        return axios.get('/user/two-factor-qr-code')
            .then(response => {
                setQrCode(response.data.svg);
            });
    };

    const showRecoveryCodes = () => {
        return axios.get('/user/two-factor-recovery-codes')
            .then(response => {
                setRecoveryCodes(response.data);
            });
    };

    const confirmTwoFactorAuthentication = () => {
        axios.post('/user/confirmed-two-factor-authentication', {
            code: confirmationCode
        })
            .then(() => {
                setEnabled(true);
                setEnabling(false);
                setConfirming(false);
            })
            .catch(error => {
                if (error.response && error.response.data.errors) {
                    setErrors(error.response.data.errors);
                }
            });
    };

    const regenerateRecoveryCodes = () => {
        axios.post('/user/two-factor-recovery-codes')
            .then(() => showRecoveryCodes());
    };

    const disableTwoFactorAuthentication = () => {
        setConfirming(false);
        setEnabling(false);
        axios.delete('/user/two-factor-authentication')
            .then(() => {
                setEnabled(false);
            });
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">{messages.two_factor_authentication || 'Two Factor Authentication'}</h2>
                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {messages.add_additional_security || 'Add additional security to your account using two factor authentication.'}
                </p>
            </header>

            <div className="mt-6">
                {enabled || enabling ? (
                    <div>
                        {qrCode && (
                            <div>
                                <p className="font-semibold text-sm text-gray-900 dark:text-gray-100 mb-4">
                                    {confirming
                                        ? messages.twofactor_confirm_text || "Two factor authentication is now enabled. Please confirm your setup by entering the authentication code provided by your authenticator application."
                                        : messages.twofactor_scan_text || "Two factor authentication is now enabled. Scan the following QR code using your phone's authenticator application or enter the setup key."}
                                </p>

                                <div dangerouslySetInnerHTML={{ __html: qrCode }} />

                                {confirming && (
                                    <div className="mt-4">
                                        <InputLabel htmlFor="code" value="Code" />
                                        <TextInput
                                            id="code"
                                            name="code"
                                            className="block mt-1 w-1/2"
                                            inputMode="numeric"
                                            autoFocus={true}
                                            autoComplete="one-time-code"
                                            value={confirmationCode}
                                            onChange={(e) => setConfirmationCode(e.target.value)}
                                        />
                                        <InputError message={errors.code} className="mt-2" />
                                    </div>
                                )}
                            </div>
                        )}

                        {recoveryCodes.length > 0 && !confirming && (
                            <div className="mt-4">
                                <p className="font-semibold text-sm text-gray-900 dark:text-gray-100 mb-4">
                                    {messages.twofactor_store_codes || "Store these recovery codes in a secure password manager. They can be used to recover access to your account if your two factor authentication device is lost."}
                                </p>
                                <div className="grid gap-1 max-w-xl mt-4 px-4 py-4 font-mono text-sm bg-gray-100 dark:bg-zinc-800 rounded-lg">
                                    {recoveryCodes.map(code => (
                                        <div key={code}>{code}</div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                ) : (
                    <div>
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                            {messages.twofactor_not_enabled || "You have not enabled two factor authentication."}
                        </p>
                    </div>
                )}

                <div className="mt-5">
                    {!enabled && !enabling ? (
                        <>
                            {hasPassword ? (
                                <ConfirmsPassword onConfirm={enableTwoFactorAuthentication}>
                                    <PrimaryButton type="button">
                                        {messages.twofactor_enable_button || "Enable"}
                                    </PrimaryButton>
                                </ConfirmsPassword>
                            ) : (
                                <PrimaryButton type="button" onClick={() => setShowSetPassword(true)}>
                                    {messages.twofactor_enable_button || "Enable"}
                                </PrimaryButton>
                            )}
                        </>
                    ) : (
                        <div className="flex items-center space-x-3">
                            {confirming ? (
                                <ConfirmsPassword onConfirm={confirmTwoFactorAuthentication}>
                                    <PrimaryButton className={enabling ? 'opacity-25' : ''} disabled={enabling}>
                                        {messages.twofactor_confirm_button || "Confirm"}
                                    </PrimaryButton>
                                </ConfirmsPassword>
                            ) : (
                                <ConfirmsPassword onConfirm={regenerateRecoveryCodes}>
                                    <SecondaryButton className="mr-3">
                                        {messages.twofactor_regenerate_button || "Regenerate Recovery Codes"}
                                    </SecondaryButton>
                                </ConfirmsPassword>
                            )}

                            {confirming ? (
                                <SecondaryButton onClick={() => { setEnabling(false); setConfirming(false); }}>
                                    {messages.cancel || "Cancel"}
                                </SecondaryButton>
                            ) : (
                                <ConfirmsPassword onConfirm={disableTwoFactorAuthentication}>
                                    <DangerButton className={enabling ? 'opacity-25' : ''} disabled={enabling}>
                                        {messages.twofactor_disable_button || "Disable"}
                                    </DangerButton>
                                </ConfirmsPassword>
                            )}
                        </div>
                    )}
                </div>
            </div >

            <Modal show={showSetPassword} onClose={() => setShowSetPassword(false)}>
                <div className="p-6">
                    <SetPasswordForm />
                    <div className="mt-6 flex justify-end">
                        <SecondaryButton onClick={() => setShowSetPassword(false)}>
                            {messages.close || "Close"}
                        </SecondaryButton>
                    </div>
                </div>
            </Modal>
        </section >
    );
}
