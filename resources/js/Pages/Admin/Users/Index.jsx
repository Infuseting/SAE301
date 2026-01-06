import { router, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Modal from '@/Components/Modal';

export default function Users({ users }) {
  const items = Array.isArray(users) ? users : users?.data || [];
  const meta = users?.meta || null;
  const [q, setQ] = useState('');
  const [modalOpen, setModalOpen] = useState(false);
  const [modalType, setModalType] = useState(null); // 'edit' | 'confirm' | 'roles'
  const [modalUser, setModalUser] = useState(null);
  const [form, setForm] = useState({ name: '', email: '' });
  const [confirmAction, setConfirmAction] = useState(null); // 'toggle' | 'delete'
  const [roles, setRoles] = useState([]);
  const [loadingRoles, setLoadingRoles] = useState(false);
  const [assigningRole, setAssigningRole] = useState(false);
  const [feedback, setFeedback] = useState(null); // { type: 'success' | 'error', message: string }

  // Check user permissions (now an array of permission names)
  const { auth } = usePage().props;
  const userPermissions = auth?.user?.permissions || [];
  const canGrantRole = userPermissions.includes('grant role');
  const canGrantAdmin = userPermissions.includes('grant admin');

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

  function openRolesModal(user) {
    setModalType('roles');
    setModalUser(user);
    setModalOpen(true);
    fetchRoles();
  }

  function fetchRoles() {
    setLoadingRoles(true);
    fetch('/admin/roles', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
    })
      .then(res => {
        if (!res.ok) throw new Error('Failed to fetch roles');
        return res.json();
      })
      .then(data => {
        setRoles(data.roles || []);
        setLoadingRoles(false);
      })
      .catch((err) => {
        console.error('Error fetching roles:', err);
        setLoadingRoles(false);
      });
  }

  function closeModal() {
    setModalOpen(false);
    setModalType(null);
    setModalUser(null);
    setConfirmAction(null);
    setFeedback(null);
    setAssigningRole(false);
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

  function assignRole(roleName) {
    if (!modalUser || assigningRole) return;
    setAssigningRole(true);
    setFeedback(null);
    router.post(
      `/admin/users/${modalUser.id}/role`,
      { role: roleName },
      {
        onSuccess: () => {
          setFeedback({ type: 'success', message: `Role "${roleName}" assigned successfully!` });
          setAssigningRole(false);
          // Close modal after short delay to show feedback
          setTimeout(() => {
            closeModal();
            setFeedback(null);
          }, 1500);
        },
        onError: (errors) => {
          setFeedback({ type: 'error', message: errors?.role || 'Failed to assign role' });
          setAssigningRole(false);
        },
      }
    );
  }

  function removeRole(roleName) {
    if (!modalUser || assigningRole) return;
    
    // Check permission for admin role
    if (roleName === 'admin' && !canGrantAdmin) {
      setFeedback({ type: 'error', message: 'You do not have permission to remove the admin role' });
      return;
    }
    
    setAssigningRole(true);
    setFeedback(null);
    router.delete(
      `/admin/users/${modalUser.id}/role`,
      {
        data: { role: roleName },
        onSuccess: () => {
          setFeedback({ type: 'success', message: `Role "${roleName}" removed successfully!` });
          setAssigningRole(false);
          setTimeout(() => {
            closeModal();
            setFeedback(null);
          }, 1500);
        },
        onError: (errors) => {
          setFeedback({ type: 'error', message: errors?.role || 'Failed to remove role' });
          setAssigningRole(false);
        },
      }
    );
  }

  const messages = usePage().props.translations?.messages || {};

  return (
    <AuthenticatedLayout>
      <div className="px-4 sm:px-6 lg:px-8 py-6">
        <div className="flex flex-col md:flex-row items-start md:items-center justify-between mb-4 gap-3">
          <h1 className="text-2xl font-bold text-black ">{messages['admin.users.title'] || 'User management'}</h1>

          <div className="flex flex-col md:flex-row gap-2 items-stretch md:items-center w-full md:w-auto">
            <input
              value={q}
              onChange={(e) => setQ(e.target.value)}
              placeholder={messages['admin.users.search_placeholder'] || 'Search name/email...'}
              className="w-full md:w-80 flex-1 min-w-0 rounded-md border px-3 py-2 text-sm bg-white  "
            />

            <button
              onClick={() => submitFilters({ page: 1 })}
              className="w-full md:w-auto rounded-md bg-gray-800 text-white px-3 py-2 text-sm"
            >
              {messages['admin.users.filter'] || 'Filter'}
            </button>

            <button
              onClick={() => { setQ(''); submitFilters({ page: 1, q: '' }); }}
              className="w-full md:w-auto rounded-md border border-gray-300  px-3 py-2 text-sm text-gray-700 "
            >
              {messages['admin.users.reset'] || 'Reset'}
            </button>
          </div>
        </div>

        <div className="overflow-x-auto bg-white  p-4 rounded-lg shadow">
          {/* Mobile stacked list */}
          <div className="space-y-3 md:hidden">
            {items.map((u) => (
              <div key={u.id} className="p-3 bg-gray-50  rounded border border-gray-100 ">
                <div className="flex items-center justify-between mb-2">
                  <div>
                    <div className="font-medium text-gray-900 ">{u.name}</div>
                    <div className="text-sm text-gray-700 ">{u.email}</div>
                  </div>
                  <div className="text-sm text-gray-900 ">{u.active ? (messages['admin.users.active'] === 'Active' ? 'Yes' : (messages['admin.users.active'] || 'Oui')) : (messages['admin.users.active'] === 'Active' ? 'No' : 'Non')}</div>
                </div>
                <div className="flex items-center justify-between">
                  <div className="text-sm text-gray-600 ">{new Date(u.created_at).toLocaleString()}</div>
                  <div className="flex gap-2">
                    <button onClick={() => openEditModal(u)} className="rounded-md border border-gray-300  px-2 py-1 text-xs text-gray-700 ">{messages['admin.users.edit'] || 'Edit'}</button>
                    {canGrantRole && (
                      <button onClick={() => openRolesModal(u)} className="rounded-md border border-blue-300 bg-blue-50 px-2 py-1 text-xs text-blue-700">{messages['admin.users.roles'] || 'Roles'}</button>
                    )}
                    <button onClick={() => openConfirmModal(u, 'toggle')} className="rounded-md border border-gray-300  px-2 py-1 text-xs text-gray-700 ">{u.active ? (messages['admin.users.deactivate'] || 'Deactivate') : (messages['admin.users.activate'] || 'Activate')}</button>
                    <button onClick={() => openConfirmModal(u, 'delete')} className="rounded-md border border-gray-300  px-2 py-1 text-xs text-red-600 ">{messages['admin.users.delete'] || 'Delete'}</button>
                  </div>
                </div>
              </div>
            ))}
          </div>

          {/* Desktop/tablet view */}
          <table className="hidden md:table min-w-full text-sm align-middle divide-y divide-gray-200 ">
            <thead>
              <tr className="text-left">
                <th className="px-3 py-2 text-xs text-gray-600 ">{messages['admin.users.name'] || 'Name'}</th>
                <th className="px-3 py-2 text-xs text-gray-600 ">{messages['admin.users.email'] || 'Email'}</th>
                <th className="px-3 py-2 text-xs text-gray-600 ">{messages['admin.users.active'] || 'Active'}</th>
                <th className="px-3 py-2 text-xs text-gray-600 ">{messages['admin.users.created_at'] || 'Created at'}</th>
                <th className="px-3 py-2 text-xs text-gray-600 ">{messages['admin.users.actions'] || 'Actions'}</th>
              </tr>
            </thead>
            <tbody>
              {items.map((u) => (
                <tr key={u.id} className="bg-white ">
                  <td className="px-3 py-3 text-gray-900 ">{u.name}</td>
                  <td className="px-3 py-3 text-gray-900 ">{u.email}</td>
                  <td className="px-3 py-3 text-gray-900 ">{u.active ? (messages['admin.users.active'] === 'Active' ? 'Yes' : (messages['admin.users.active'] || 'Oui')) : (messages['admin.users.active'] === 'Active' ? 'No' : 'Non')}</td>
                  <td className="px-3 py-3 text-gray-700  whitespace-nowrap">{new Date(u.created_at).toLocaleString()}</td>
                  <td className="px-3 py-3 text-gray-900 ">
                    <div className="flex gap-2">
                      <button onClick={() => openEditModal(u)} className="rounded-md border border-gray-300  px-2 py-1 text-xs text-gray-700 ">{messages['admin.users.edit'] || 'Edit'}</button>
                      {canGrantRole && (
                        <button onClick={() => openRolesModal(u)} className="rounded-md border border-blue-300 bg-blue-50 px-2 py-1 text-xs text-blue-700">{messages['admin.users.roles'] || 'Roles'}</button>
                      )}
                      <button onClick={() => openConfirmModal(u, 'toggle')} className="rounded-md border border-gray-300  px-2 py-1 text-xs text-gray-700 ">{u.active ? (messages['admin.users.deactivate'] || 'Deactivate') : (messages['admin.users.activate'] || 'Activate')}</button>
                      <button onClick={() => openConfirmModal(u, 'delete')} className="rounded-md border border-gray-300  px-2 py-1 text-xs text-red-600 ">{messages['admin.users.delete'] || 'Delete'}</button>
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
              <h3 className="text-lg font-medium text-black  mb-4">{(messages['admin.users.edit_title'] || 'Edit :name').replace(':name', modalUser.name)}</h3>
              <div className="space-y-3">
                <div>
                  <label className="block text-sm text-gray-700 ">Nom</label>
                  <input value={form.name} onChange={(e) => setForm((s) => ({ ...s, name: e.target.value }))} className="mt-1 block w-full rounded-md border px-3 py-2 bg-white  " />
                </div>
                <div>
                  <label className="block text-sm text-gray-700 ">Email</label>
                  <input value={form.email} onChange={(e) => setForm((s) => ({ ...s, email: e.target.value }))} className="mt-1 block w-full rounded-md border px-3 py-2 bg-white  " />
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
              <h3 className="text-lg font-medium text-black  mb-4">{confirmAction === 'delete' ? (messages['admin.users.delete'] || 'Confirm delete') : (messages['confirm_button'] || 'Confirm')}</h3>
              <p className="text-sm text-gray-700 ">{confirmAction === 'delete' ? (messages['admin.users.confirm_delete'] || `Permanently delete ${modalUser.name}? This action cannot be undone.`).replace(':name', modalUser.name) : (messages['admin.users.confirm_toggle'] || `${modalUser.active ? 'Deactivate' : 'Activate'} user ${modalUser.name}?`).replace(':name', modalUser.name).replace(':action', modalUser.active ? (messages['admin.users.deactivate'] || 'Deactivate') : (messages['admin.users.activate'] || 'Activate'))}</p>
              <div className="flex justify-end gap-2 pt-4">
                <button onClick={closeModal} className="rounded-md border px-3 py-2 text-sm">Annuler</button>
                <button onClick={confirmSubmit} className="rounded-md bg-gray-800 text-white px-3 py-2 text-sm">Confirmer</button>
              </div>
            </div>
          )}

          {modalType === 'roles' && modalUser && (
            <div className="p-4">
              <h3 className="text-lg font-medium text-black mb-4">{(messages['admin.users.manage_roles_title'] || 'Manage roles for :name').replace(':name', modalUser.name)}</h3>
              
              {/* Current roles display with remove buttons */}
              {modalUser.role_names && modalUser.role_names.length > 0 && (
                <div className="mb-4 p-3 bg-gray-50 rounded-md border border-gray-200">
                  <p className="text-xs text-gray-500 mb-2">{messages['admin.users.current_roles'] || 'Current roles:'}</p>
                  <div className="flex flex-wrap gap-2">
                    {modalUser.role_names.map((roleName) => {
                      const isAdminRole = roleName === 'admin';
                      const canRemove = canGrantRole && (!isAdminRole || canGrantAdmin);
                      return (
                        <span key={roleName} className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 capitalize">
                          {roleName}
                          {canRemove && (
                            <button
                              onClick={() => removeRole(roleName)}
                              disabled={assigningRole}
                              className="ml-1 hover:bg-blue-200 rounded-full p-0.5 transition-colors disabled:opacity-50"
                              title={messages['admin.users.remove_role'] || 'Remove role'}
                            >
                              <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                              </svg>
                            </button>
                          )}
                        </span>
                      );
                    })}
                  </div>
                </div>
              )}

              {/* Feedback message */}
              {feedback && (
                <div className={`mb-4 p-3 rounded-md text-sm ${
                  feedback.type === 'success' 
                    ? 'bg-green-50 text-green-800 border border-green-200' 
                    : 'bg-red-50 text-red-800 border border-red-200'
                }`}>
                  {feedback.message}
                </div>
              )}

              {loadingRoles ? (
                <p className="text-sm text-gray-500">{messages['loading'] || 'Loading...'}</p>
              ) : (
                <div className="space-y-2">
                  <p className="text-xs text-gray-500 mb-2">{messages['admin.users.select_role'] || 'Click to add a role:'}</p>
                  {roles.map((role) => {
                    const isAdmin = role.name === 'admin';
                    const disabled = (isAdmin && !canGrantAdmin) || assigningRole;
                    const isCurrentRole = modalUser.role_names?.includes(role.name);
                    return (
                      <button
                        key={role.id}
                        onClick={() => !disabled && !isCurrentRole && assignRole(role.name)}
                        disabled={disabled || isCurrentRole}
                        className={`w-full text-left px-4 py-3 rounded-md border transition-colors flex items-center justify-between ${
                          isCurrentRole
                            ? 'bg-blue-50 text-blue-700 border-blue-300 cursor-default'
                            : disabled
                              ? 'bg-gray-100 text-gray-400 cursor-not-allowed border-gray-200'
                              : 'bg-white hover:bg-blue-50 hover:border-blue-300 text-gray-800 border-gray-300'
                        }`}
                      >
                        <span className="font-medium capitalize">{role.name}</span>
                        <span className="flex items-center gap-2">
                          {isCurrentRole && (
                            <span className="text-xs bg-blue-200 text-blue-800 px-2 py-0.5 rounded-full">
                              {messages['admin.users.current'] || 'Current'}
                            </span>
                          )}
                          {isAdmin && !canGrantAdmin && !isCurrentRole && (
                            <span className="text-xs text-red-500">({messages['admin.users.requires_grant_admin'] || 'Requires permission'})</span>
                          )}
                          {assigningRole && !isCurrentRole && (
                            <svg className="animate-spin h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                              <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                              <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                          )}
                        </span>
                      </button>
                    );
                  })}
                </div>
              )}
              <div className="flex justify-end gap-2 pt-4">
                <button onClick={closeModal} className="rounded-md border px-3 py-2 text-sm" disabled={assigningRole}>{messages['cancel'] || 'Cancel'}</button>
              </div>
            </div>
          )}
        </Modal>

        {meta && (
          <div className="mt-6 flex items-center justify-between gap-3">
            <div className="text-sm text-gray-600 ">Page {meta.current_page} / {meta.last_page} — {meta.total} utilisateurs</div>
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
