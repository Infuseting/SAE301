import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Modal from '@/Components/Modal';

export default function Users({ users }) {
  const items = Array.isArray(users) ? users : users?.data || [];
  const meta = users?.meta || null;
  const [q, setQ] = useState('');
  const [modalOpen, setModalOpen] = useState(false);
  const [modalType, setModalType] = useState(null); // 'edit' | 'confirm'
  const [modalUser, setModalUser] = useState(null);
  const [form, setForm] = useState({ name: '', email: '' });
  const [confirmAction, setConfirmAction] = useState(null); // 'toggle' | 'delete'

  function submitFilters(params = {}) {
    const query = { q, ...params };
    router.get(window.route ? window.route('admin.users.index') : '/admin/users', query, { preserveState: true, replace: true });
  }

  function openEditModal(user) {
    setModalType('edit');
    setModalUser(user);
    setForm({ name: user.name || '', email: user.email || '' });
    setModalOpen(true);
  }

  function openConfirmModal(user, action) {
    setModalType('confirm');
    setModalUser(user);
    setConfirmAction(action);
    setModalOpen(true);
  }

  function closeModal() {
    setModalOpen(false);
    setModalType(null);
    setModalUser(null);
    setConfirmAction(null);
  }

  function submitEdit() {
    if (!modalUser) return closeModal();
    router.post(
      window.route ? window.route('admin.users.update', modalUser.id) : `/admin/users/${modalUser.id}`,
      {
        _method: 'PUT',
        name: form.name,
        email: form.email,
      },
      { onSuccess: () => closeModal() }
    );
  }

  function confirmSubmit() {
    if (!modalUser || !confirmAction) return closeModal();
    if (confirmAction === 'toggle') {
      router.post(
        window.route ? window.route('admin.users.toggle', modalUser.id) : `/admin/users/${modalUser.id}/toggle`,
        {},
        { onSuccess: () => closeModal() }
      );
    }
    if (confirmAction === 'delete') {
      router.delete(
        window.route ? window.route('admin.users.destroy', modalUser.id) : `/admin/users/${modalUser.id}`,
        { onSuccess: () => closeModal() }
      );
    }
  }

  const messages = usePage().props.translations?.messages || {};

  return (
    <AuthenticatedLayout>
      <div className="px-4 sm:px-6 lg:px-8 py-6">
        <div className="flex flex-col md:flex-row items-start md:items-center justify-between mb-4 gap-3">
          <h1 className="text-2xl font-bold text-black dark:text-white">{messages['admin.users.title'] || 'User management'}</h1>

          <div className="flex flex-col md:flex-row gap-2 items-stretch md:items-center w-full md:w-auto">
            <input
              value={q}
              onChange={(e) => setQ(e.target.value)}
              placeholder={messages['admin.users.search_placeholder'] || 'Search name/email...'}
              className="w-full md:w-80 flex-1 min-w-0 rounded-md border px-3 py-2 text-sm bg-white dark:bg-zinc-800 dark:text-white"
            />

            <button
              onClick={() => submitFilters({ page: 1 })}
              className="w-full md:w-auto rounded-md bg-gray-800 text-white px-3 py-2 text-sm"
            >
              {messages['admin.users.filter'] || 'Filter'}
            </button>

            <button
              onClick={() => { setQ(''); submitFilters({ page: 1, q: '' }); }}
              className="w-full md:w-auto rounded-md border border-gray-300 dark:border-zinc-600 px-3 py-2 text-sm text-gray-700 dark:text-gray-200"
            >
              {messages['admin.users.reset'] || 'Reset'}
            </button>
          </div>
        </div>

        <div className="overflow-x-auto bg-white dark:bg-zinc-900 p-4 rounded-lg shadow">
          {/* Mobile stacked list */}
          <div className="space-y-3 md:hidden">
            {items.map((u) => (
              <div key={u.id} className="p-3 bg-gray-50 dark:bg-zinc-800 rounded border border-gray-100 dark:border-zinc-700">
                <div className="flex items-center justify-between mb-2">
                  <div>
                    <div className="font-medium text-gray-900 dark:text-gray-100">{u.name}</div>
                    <div className="text-sm text-gray-700 dark:text-gray-300">{u.email}</div>
                  </div>
                  <div className="text-sm text-gray-900 dark:text-gray-100">{u.active ? (messages['admin.users.active'] === 'Active' ? 'Yes' : (messages['admin.users.active'] || 'Oui')) : (messages['admin.users.active'] === 'Active' ? 'No' : 'Non')}</div>
                </div>
                <div className="flex items-center justify-between">
                  <div className="text-sm text-gray-600 dark:text-gray-300">{new Date(u.created_at).toLocaleString()}</div>
                  <div className="flex gap-2">
                    <button onClick={() => openEditModal(u)} className="rounded-md border border-gray-300 dark:border-zinc-600 px-2 py-1 text-xs text-gray-700 dark:text-gray-200">{messages['admin.users.edit'] || 'Edit'}</button>
                    <button onClick={() => openConfirmModal(u, 'toggle')} className="rounded-md border border-gray-300 dark:border-zinc-600 px-2 py-1 text-xs text-gray-700 dark:text-gray-200">{u.active ? (messages['admin.users.deactivate'] || 'Deactivate') : (messages['admin.users.activate'] || 'Activate')}</button>
                    <button onClick={() => openConfirmModal(u, 'delete')} className="rounded-md border border-gray-300 dark:border-zinc-600 px-2 py-1 text-xs text-red-600 dark:text-red-400">{messages['admin.users.delete'] || 'Delete'}</button>
                  </div>
                </div>
              </div>
            ))}
          </div>

          {/* Desktop/tablet view */}
          <table className="hidden md:table min-w-full text-sm align-middle divide-y divide-gray-200 dark:divide-zinc-700">
            <thead>
              <tr className="text-left">
                <th className="px-3 py-2 text-xs text-gray-600 dark:text-gray-300">{messages['admin.users.name'] || 'Name'}</th>
                <th className="px-3 py-2 text-xs text-gray-600 dark:text-gray-300">{messages['admin.users.email'] || 'Email'}</th>
                <th className="px-3 py-2 text-xs text-gray-600 dark:text-gray-300">{messages['admin.users.active'] || 'Active'}</th>
                <th className="px-3 py-2 text-xs text-gray-600 dark:text-gray-300">{messages['admin.users.created_at'] || 'Created at'}</th>
                <th className="px-3 py-2 text-xs text-gray-600 dark:text-gray-300">{messages['admin.users.actions'] || 'Actions'}</th>
              </tr>
            </thead>
            <tbody>
              {items.map((u) => (
                <tr key={u.id} className="bg-white dark:bg-zinc-900">
                  <td className="px-3 py-3 text-gray-900 dark:text-gray-100">{u.name}</td>
                  <td className="px-3 py-3 text-gray-900 dark:text-gray-100">{u.email}</td>
                  <td className="px-3 py-3 text-gray-900 dark:text-gray-100">{u.active ? (messages['admin.users.active'] === 'Active' ? 'Yes' : (messages['admin.users.active'] || 'Oui')) : (messages['admin.users.active'] === 'Active' ? 'No' : 'Non')}</td>
                  <td className="px-3 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">{new Date(u.created_at).toLocaleString()}</td>
                  <td className="px-3 py-3 text-gray-900 dark:text-gray-100">
                    <div className="flex gap-2">
                      <button onClick={() => openEditModal(u)} className="rounded-md border border-gray-300 dark:border-zinc-600 px-2 py-1 text-xs text-gray-700 dark:text-gray-200">{messages['admin.users.edit'] || 'Edit'}</button>
                      <button onClick={() => openConfirmModal(u, 'toggle')} className="rounded-md border border-gray-300 dark:border-zinc-600 px-2 py-1 text-xs text-gray-700 dark:text-gray-200">{u.active ? (messages['admin.users.deactivate'] || 'Deactivate') : (messages['admin.users.activate'] || 'Activate')}</button>
                      <button onClick={() => openConfirmModal(u, 'delete')} className="rounded-md border border-gray-300 dark:border-zinc-600 px-2 py-1 text-xs text-red-600 dark:text-red-400">{messages['admin.users.delete'] || 'Delete'}</button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        <Modal show={modalOpen} onClose={closeModal} maxWidth="md">
          {modalType === 'edit' && modalUser && (
            <div className="p-4">
              <h3 className="text-lg font-medium text-black dark:text-white mb-4">{(messages['admin.users.edit_title'] || 'Edit :name').replace(':name', modalUser.name)}</h3>
              <div className="space-y-3">
                <div>
                  <label className="block text-sm text-gray-700 dark:text-gray-300">Nom</label>
                  <input value={form.name} onChange={(e) => setForm((s) => ({ ...s, name: e.target.value }))} className="mt-1 block w-full rounded-md border px-3 py-2 bg-white dark:bg-zinc-800 dark:text-white" />
                </div>
                <div>
                  <label className="block text-sm text-gray-700 dark:text-gray-300">Email</label>
                  <input value={form.email} onChange={(e) => setForm((s) => ({ ...s, email: e.target.value }))} className="mt-1 block w-full rounded-md border px-3 py-2 bg-white dark:bg-zinc-800 dark:text-white" />
                </div>
                <div className="flex justify-end gap-2 pt-2">
                  <button onClick={closeModal} className="rounded-md border px-3 py-2 text-sm">{messages['cancel'] || 'Cancel'}</button>
                  <button onClick={submitEdit} className="rounded-md bg-gray-800 text-white px-3 py-2 text-sm">{messages['save'] || 'Save'}</button>
                </div>
              </div>
            </div>
          )}

          {modalType === 'confirm' && modalUser && (
            <div className="p-4">
              <h3 className="text-lg font-medium text-black dark:text-white mb-4">{confirmAction === 'delete' ? (messages['admin.users.delete'] || 'Confirm delete') : (messages['confirm_button'] || 'Confirm')}</h3>
              <p className="text-sm text-gray-700 dark:text-gray-300">{confirmAction === 'delete' ? (messages['admin.users.confirm_delete'] || `Permanently delete ${modalUser.name}? This action cannot be undone.`).replace(':name', modalUser.name) : (messages['admin.users.confirm_toggle'] || `${modalUser.active ? 'Deactivate' : 'Activate'} user ${modalUser.name}?`).replace(':name', modalUser.name).replace(':action', modalUser.active ? (messages['admin.users.deactivate'] || 'Deactivate') : (messages['admin.users.activate'] || 'Activate'))}</p>
              <div className="flex justify-end gap-2 pt-4">
                <button onClick={closeModal} className="rounded-md border px-3 py-2 text-sm">Annuler</button>
                <button onClick={confirmSubmit} className="rounded-md bg-gray-800 text-white px-3 py-2 text-sm">Confirmer</button>
              </div>
            </div>
          )}
        </Modal>

        {meta && (
          <div className="mt-6 flex items-center justify-between gap-3">
            <div className="text-sm text-gray-600 dark:text-gray-300">Page {meta.current_page} / {meta.last_page} — {meta.total} utilisateurs</div>
            <div className="flex gap-2">
              <button disabled={!meta.prev_page_url} onClick={() => submitFilters({ page: Math.max(1, meta.current_page - 1) })} className="rounded-md border px-3 py-2 text-sm disabled:opacity-50">Précédent</button>
              <button disabled={!meta.next_page_url} onClick={() => submitFilters({ page: meta.current_page + 1 })} className="rounded-md border px-3 py-2 text-sm disabled:opacity-50">Suivant</button>
            </div>
          </div>
        )}
      </div>
    </AuthenticatedLayout>
  );
}
