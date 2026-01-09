import { Fragment } from 'react';
import { Dialog, Transition } from '@headlessui/react';
import { X, Users, Mail, User } from 'lucide-react';

/**
 * RegisteredMembersModal component - Displays list of members registered to raid courses
 * 
 * @param {boolean} isOpen - Whether modal is open
 * @param {function} onClose - Function to close modal
 * @param {array} members - Array of registered members
 */
export default function RegisteredMembersModal({ isOpen, onClose, members = [] }) {
    return (
        <Transition appear show={isOpen} as={Fragment}>
            <Dialog as="div" className="relative z-50" onClose={onClose}>
                <Transition.Child
                    as={Fragment}
                    enter="ease-out duration-300"
                    enterFrom="opacity-0"
                    enterTo="opacity-100"
                    leave="ease-in duration-200"
                    leaveFrom="opacity-100"
                    leaveTo="opacity-0"
                >
                    <div className="fixed inset-0 bg-black bg-opacity-25 backdrop-blur-sm" />
                </Transition.Child>

                <div className="fixed inset-0 overflow-y-auto">
                    <div className="flex min-h-full items-center justify-center p-4 text-center">
                        <Transition.Child
                            as={Fragment}
                            enter="ease-out duration-300"
                            enterFrom="opacity-0 scale-95"
                            enterTo="opacity-100 scale-100"
                            leave="ease-in duration-200"
                            leaveFrom="opacity-100 scale-100"
                            leaveTo="opacity-0 scale-95"
                        >
                            <Dialog.Panel className="w-full max-w-2xl max-h-[90vh] transform overflow-hidden rounded-3xl bg-white text-left align-middle shadow-xl transition-all flex flex-col">
                                {/* Header */}
                                <div className="flex items-start justify-between p-8 pb-4 flex-shrink-0">
                                    <div className="flex items-center gap-3">
                                        <div className="bg-emerald-100 p-3 rounded-2xl">
                                            <Users className="h-6 w-6 text-emerald-600" />
                                        </div>
                                        <div>
                                            <Dialog.Title
                                                as="h3"
                                                className="text-2xl font-black text-gray-900 uppercase italic"
                                            >
                                                Membres inscrits
                                            </Dialog.Title>
                                            <p className="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1">
                                                {members.length} participant{members.length > 1 ? 's' : ''}
                                            </p>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        className="rounded-full p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 transition-colors"
                                        onClick={onClose}
                                    >
                                        <X className="h-5 w-5" />
                                    </button>
                                </div>

                                {/* Members List */}
                                <div className="space-y-3 overflow-y-auto px-8 pb-8 flex-1">
                                    {members.length === 0 ? (
                                        <div className="text-center py-12">
                                            <Users className="h-12 w-12 text-gray-300 mx-auto mb-4" />
                                            <p className="text-sm font-semibold text-gray-500">
                                                Aucun membre inscrit pour le moment
                                            </p>
                                        </div>
                                    ) : (
                                        members.map((member) => (
                                            <div
                                                key={member.id}
                                                className="bg-gray-50 rounded-2xl p-4 hover:bg-gray-100 transition-colors border border-gray-100"
                                            >
                                                <div className="flex items-center gap-4">
                                                    <div className="bg-emerald-100 p-3 rounded-xl">
                                                        <User className="h-5 w-5 text-emerald-600" />
                                                    </div>
                                                    <div className="flex-1">
                                                        <h4 className="text-sm font-bold text-gray-900">
                                                            {member.name}
                                                        </h4>
                                                        <div className="flex items-center gap-1.5 text-xs text-gray-500 mt-1">
                                                            <Mail className="h-3 w-3" />
                                                            {member.email}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        ))
                                    )}
                                </div>

                                {/* Footer */}
                                <div className="mt-6 flex justify-end">
                                    <button
                                        type="button"
                                        className="bg-gray-900 text-white px-6 py-3 rounded-2xl font-black text-xs transition-all hover:bg-gray-800 uppercase tracking-widest"
                                        onClick={onClose}
                                    >
                                        Fermer
                                    </button>
                                </div>
                            </Dialog.Panel>
                        </Transition.Child>
                    </div>
                </div>
            </Dialog>
        </Transition>
    );
}
