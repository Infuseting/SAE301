import { router, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Modal from '@/Components/Modal';
import Pagination from '@/Components/Pagination';

export default function Users({ users, filters }) {
  const items = Array.isArray(users) ? users : users?.data || [];
  const [q, setQ] = useState(filters?.q || '');
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

  /**
   * Submit search and pagination filters using POST to hide all URL parameters
   */
  function submitFilters(params = {}) {
    const data = { q, page: 1, ...params };
    router.post(
      window.route ? window.route('admin.users.index') : '/admin/users',
      data,
      {
        preserveState: true,
        preserveScroll: true,
        only: ['users', 'filters'],
      }
    );
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

        <div className="overflow-x-auto bg-white p-4 rounded-lg shadow">
          {/* Mobile stacked list */}
          <div className="space-y-4 md:hidden">
            {items.map((u) => (
              <div key={u.id} className="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                {/* Header with name and status */}
                <div className="bg-gradient-to-r from-gray-50 to-gray-100 px-4 py-3 border-b border-gray-200">
                  <div className="flex items-start justify-between">
                    <div className="flex items-center flex-1 min-w-0">
                      {/* Avatar */}
                      <div className="flex-shrink-0 mr-3">
                        {u.profile_photo_url ? (
                          <img 
                            src={u.profile_photo_url} 
                            alt={u.name}
                            className="h-12 w-12 rounded-full object-cover border-2 border-white shadow-sm"
                          />
                        ) : (
                          <div className="h-12 w-12 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-semibold text-sm border-2 border-white shadow-sm">
                            {u.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()}
                          </div>
                        )}
                      </div>
                      {/* Name and email */}
                      <div className="flex-1 min-w-0">
                        <h3 className="font-semibold text-gray-900 text-base truncate">{u.name}</h3>
                        <p className="text-sm text-gray-600 truncate mt-0.5">{u.email}</p>
                      </div>
                    </div>
                    <span className={`ml-3 flex-shrink-0 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${
                      u.active 
                        ? 'bg-green-100 text-green-800' 
                        : 'bg-red-100 text-red-800'
                    }`}>
                      {u.active ? `✓ ${messages['admin.users.status_active'] || 'Actif'}` : `✕ ${messages['admin.users.status_inactive'] || 'Inactif'}`}
                    </span>
                  </div>
                </div>

                {/* User details */}
                <div className="px-4 py-3 space-y-2 bg-white">
                  {/* Roles if any */}
                  {u.role_names && u.role_names.length > 0 && (
                    <div className="flex items-center gap-2 flex-wrap">
                      <span className="text-xs font-medium text-gray-500">{messages['admin.users.roles'] || 'Rôles'}:</span>
                      {u.role_names.map((role) => (
                        <span key={role} className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 capitalize">
                          {role}
                        </span>
                      ))}
                    </div>
                  )}
                  
                  {/* Creation date */}
                  <div className="flex items-center text-xs text-gray-500">
                    <svg className="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Créé le {new Date(u.created_at).toLocaleDateString('fr-FR')}
                  </div>
                </div>

                {/* Action buttons */}
                <div className="bg-gray-50 px-4 py-3 border-t border-gray-200">
                  <div className="grid grid-cols-2 gap-2">
                    <button 
                      onClick={() => openEditModal(u)} 
                      className="flex items-center justify-center px-3 py-2 rounded-md border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors"
                    >
                      <svg className="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                      {messages['admin.users.edit'] || 'Éditer'}
                    </button>
                    
                    {canGrantRole && (
                      <button 
                        onClick={() => openRolesModal(u)} 
                        className="flex items-center justify-center px-3 py-2 rounded-md border border-blue-300 bg-blue-50 text-blue-700 text-sm font-medium hover:bg-blue-100 transition-colors"
                      >
                        <svg className="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        {messages['admin.users.roles'] || 'Rôles'}
                      </button>
                    )}
                    
                    <button 
                      onClick={() => openConfirmModal(u, 'toggle')} 
                      className={`flex items-center justify-center px-3 py-2 rounded-md border text-sm font-medium transition-colors ${
                        u.active
                          ? 'border-orange-300 bg-orange-50 text-orange-700 hover:bg-orange-100'
                          : 'border-green-300 bg-green-50 text-green-700 hover:bg-green-100'
                      }`}
                    >
                      <svg className="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d={u.active ? "M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" : "M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"} />
                      </svg>
                      {u.active ? (messages['admin.users.deactivate'] || 'Désactiver') : (messages['admin.users.activate'] || 'Activer')}
                    </button>
                    
                    <button 
                      onClick={() => openConfirmModal(u, 'delete')} 
                      className="flex items-center justify-center px-3 py-2 rounded-md border border-red-300 bg-red-50 text-red-700 text-sm font-medium hover:bg-red-100 transition-colors"
                    >
                      <svg className="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                      {messages['admin.users.delete'] || 'Supprimer'}
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>

          {/* Desktop/tablet view */}
          <table className="hidden md:table min-w-full text-sm align-middle">
            <thead className="bg-gray-50 border-b-2 border-gray-200">
              <tr className="text-left">
                <th className="px-4 py-3 text-xs font-semibold text-gray-700 uppercase tracking-wider">{messages['admin.users.name'] || 'Nom'}</th>
                <th className="px-4 py-3 text-xs font-semibold text-gray-700 uppercase tracking-wider">{messages['admin.users.email'] || 'Email'}</th>
                <th className="px-4 py-3 text-xs font-semibold text-gray-700 uppercase tracking-wider">{messages['admin.users.roles'] || 'Rôles'}</th>
                <th className="px-4 py-3 text-xs font-semibold text-gray-700 uppercase tracking-wider">{messages['admin.users.active'] || 'Statut'}</th>
                <th className="px-4 py-3 text-xs font-semibold text-gray-700 uppercase tracking-wider">{messages['admin.users.created_at'] || 'Créé le'}</th>
                <th className="px-4 py-3 text-xs font-semibold text-gray-700 uppercase tracking-wider text-right">{messages['admin.users.actions'] || 'Actions'}</th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {items.map((u) => (
                <tr key={u.id} className="hover:bg-gray-50 transition-colors">
                  <td className="px-4 py-4">
                    <div className="flex items-center">
                      <div className="flex-shrink-0 h-10 w-10">
                        {u.profile_photo_url ? (
                          <img 
                            src={u.profile_photo_url} 
                            alt={u.name}
                            className="h-10 w-10 rounded-full object-cover"
                          />
                        ) : (
                          <div className="h-10 w-10 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-semibold">
                            {u.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()}
                          </div>
                        )}
                      </div>
                      <div className="ml-3">
                        <div className="text-sm font-semibold text-gray-900">{u.name}</div>
                      </div>
                    </div>
                  </td>
                  <td className="px-4 py-4 text-sm text-gray-700">{u.email}</td>
                  <td className="px-4 py-4">
                    {u.role_names && u.role_names.length > 0 ? (
                      <div className="flex flex-wrap gap-1">
                        {u.role_names.map((role) => (
                          <span key={role} className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 capitalize">
                            {role}
                          </span>
                        ))}
                      </div>
                    ) : (
                      <span className="text-xs text-gray-400 italic">{messages['admin.users.no_roles'] || 'Aucun rôle'}</span>
                    )}
                  </td>
                  <td className="px-4 py-4">
                    <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ${
                      u.active 
                        ? 'bg-green-100 text-green-800' 
                        : 'bg-red-100 text-red-800'
                    }`}>
                      {u.active ? `✓ ${messages['admin.users.status_active'] || 'Actif'}` : `✕ ${messages['admin.users.status_inactive'] || 'Inactif'}`}
                    </span>
                  </td>
                  <td className="px-4 py-4 whitespace-nowrap">
                    <div className="flex items-center text-xs text-gray-500">
                      <svg className="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      {new Date(u.created_at).toLocaleDateString('fr-FR')}
                    </div>
                  </td>
                  <td className="px-4 py-4 text-right">
                    <div className="flex gap-2 justify-end">
                      <button 
                        onClick={() => openEditModal(u)} 
                        className="inline-flex items-center px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 text-xs font-medium hover:bg-gray-50 transition-colors"
                        title={messages['admin.users.edit'] || 'Éditer'}
                      >
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                      </button>
                      {canGrantRole && (
                        <button 
                          onClick={() => openRolesModal(u)} 
                          className="inline-flex items-center px-3 py-1.5 rounded-md border border-blue-300 bg-blue-50 text-blue-700 text-xs font-medium hover:bg-blue-100 transition-colors"
                          title={messages['admin.users.roles'] || 'Rôles'}
                        >
                          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                          </svg>
                        </button>
                      )}
                      <button 
                        onClick={() => openConfirmModal(u, 'toggle')} 
                        className={`inline-flex items-center px-3 py-1.5 rounded-md border text-xs font-medium transition-colors ${
                          u.active
                            ? 'border-orange-300 bg-orange-50 text-orange-700 hover:bg-orange-100'
                            : 'border-green-300 bg-green-50 text-green-700 hover:bg-green-100'
                        }`}
                        title={u.active ? (messages['admin.users.deactivate'] || 'Désactiver') : (messages['admin.users.activate'] || 'Activer')}
                      >
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d={u.active ? "M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" : "M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"} />
                        </svg>
                      </button>
                      <button 
                        onClick={() => openConfirmModal(u, 'delete')} 
                        className="inline-flex items-center px-3 py-1.5 rounded-md border border-red-300 bg-red-50 text-red-700 text-xs font-medium hover:bg-red-100 transition-colors"
                        title={messages['admin.users.delete'] || 'Supprimer'}
                      >
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
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

        <Pagination pagination={users} onPageChange={(page) => submitFilters({ page })} />
      </div>
    </AuthenticatedLayout>
  );
}
