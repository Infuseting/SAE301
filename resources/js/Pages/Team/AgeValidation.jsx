import { useState, useCallback } from 'react';
import { Head, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';

/**
 * Team Age Validation Page
 * 
 * Allows users to validate team compositions based on age requirements.
 * Rules: All participants >= A, and if any < B then at least one >= C
 */
export default function AgeValidation({ thresholds, rules }) {
    const { auth } = usePage().props;
    const messages = usePage().props.translations?.messages || {};
    
    const [participants, setParticipants] = useState([{ age: '', birthdate: '' }]);
    const [validationMode, setValidationMode] = useState('age'); // 'age' or 'birthdate'
    const [result, setResult] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    /**
     * Add a new participant row.
     */
    const addParticipant = useCallback(() => {
        setParticipants(prev => [...prev, { age: '', birthdate: '' }]);
        setResult(null);
    }, []);

    /**
     * Remove a participant row.
     */
    const removeParticipant = useCallback((index) => {
        if (participants.length > 1) {
            setParticipants(prev => prev.filter((_, i) => i !== index));
            setResult(null);
        }
    }, [participants.length]);

    /**
     * Update participant data.
     */
    const updateParticipant = useCallback((index, field, value) => {
        setParticipants(prev => {
            const updated = [...prev];
            updated[index] = { ...updated[index], [field]: value };
            return updated;
        });
        setResult(null);
    }, []);

    /**
     * Validate the team composition.
     */
    const validateTeam = async () => {
        setLoading(true);
        setError(null);
        setResult(null);

        try {
            let response;
            
            if (validationMode === 'age') {
                const ages = participants
                    .map(p => parseInt(p.age, 10))
                    .filter(age => !isNaN(age));

                if (ages.length === 0) {
                    setError('Please enter at least one valid age.');
                    setLoading(false);
                    return;
                }

                response = await fetch('/api/team/validate-ages', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ ages }),
                });
            } else {
                const birthdates = participants
                    .map(p => p.birthdate)
                    .filter(bd => bd);

                if (birthdates.length === 0) {
                    setError('Please enter at least one valid birthdate.');
                    setLoading(false);
                    return;
                }

                response = await fetch('/api/team/validate-birthdates', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ birthdates }),
                });
            }

            const data = await response.json();
            
            if (!response.ok) {
                setError(data.message || 'Validation failed.');
                return;
            }

            setResult(data);
        } catch (err) {
            setError('An error occurred during validation.');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    /**
     * Reset the form.
     */
    const resetForm = () => {
        setParticipants([{ age: '', birthdate: '' }]);
        setResult(null);
        setError(null);
    };

    /**
     * Get participant status badge.
     */
    const getParticipantBadge = (age) => {
        if (age < thresholds.min) {
            return <span className="px-2 py-1 text-xs font-semibold bg-red-100 text-red-800 rounded">Too Young</span>;
        }
        if (age < thresholds.intermediate) {
            return <span className="px-2 py-1 text-xs font-semibold bg-yellow-100 text-yellow-800 rounded">Minor (needs adult)</span>;
        }
        if (age >= thresholds.adult) {
            return <span className="px-2 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded">Adult (can supervise)</span>;
        }
        return <span className="px-2 py-1 text-xs font-semibold bg-blue-100 text-blue-800 rounded">Intermediate</span>;
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">
                {messages.team_age_validation || 'Team Age Validation'}
            </h2>}
        >
            <Head title={messages.team_age_validation || 'Team Age Validation'} />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    {/* Rules Card */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-3">
                                {messages.age_rules || 'Age Requirements'}
                            </h3>
                            <p className="text-gray-600 mb-4">{rules}</p>
                            
                            <div className="grid grid-cols-3 gap-4 mt-4">
                                <div className="bg-gray-50 p-4 rounded-lg text-center">
                                    <div className="text-2xl font-bold text-gray-900">{thresholds.min}</div>
                                    <div className="text-sm text-gray-500">Minimum Age (A)</div>
                                </div>
                                <div className="bg-yellow-50 p-4 rounded-lg text-center">
                                    <div className="text-2xl font-bold text-yellow-700">{thresholds.intermediate}</div>
                                    <div className="text-sm text-gray-500">Intermediate (B)</div>
                                </div>
                                <div className="bg-green-50 p-4 rounded-lg text-center">
                                    <div className="text-2xl font-bold text-green-700">{thresholds.adult}</div>
                                    <div className="text-sm text-gray-500">Adult Age (C)</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Validation Form */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex justify-between items-center mb-6">
                                <h3 className="text-lg font-semibold text-gray-900">
                                    {messages.validate_team || 'Validate Team'}
                                </h3>
                                
                                {/* Mode Toggle */}
                                <div className="flex items-center space-x-2">
                                    <button
                                        type="button"
                                        onClick={() => setValidationMode('age')}
                                        className={`px-3 py-1 text-sm rounded-l-lg border ${
                                            validationMode === 'age'
                                                ? 'bg-emerald-600 text-white border-emerald-600'
                                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                        }`}
                                    >
                                        By Age
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setValidationMode('birthdate')}
                                        className={`px-3 py-1 text-sm rounded-r-lg border-t border-r border-b ${
                                            validationMode === 'birthdate'
                                                ? 'bg-emerald-600 text-white border-emerald-600'
                                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                        }`}
                                    >
                                        By Birthdate
                                    </button>
                                </div>
                            </div>

                            {/* Participants List */}
                            <div className="space-y-4">
                                {participants.map((participant, index) => (
                                    <div key={index} className="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
                                        <div className="flex-shrink-0 w-8 h-8 bg-emerald-600 text-white rounded-full flex items-center justify-center text-sm font-semibold">
                                            {index + 1}
                                        </div>
                                        
                                        {validationMode === 'age' ? (
                                            <div className="flex-grow">
                                                <InputLabel htmlFor={`age-${index}`} value="Age" className="sr-only" />
                                                <TextInput
                                                    id={`age-${index}`}
                                                    type="number"
                                                    min="0"
                                                    max="150"
                                                    value={participant.age}
                                                    onChange={(e) => updateParticipant(index, 'age', e.target.value)}
                                                    placeholder="Enter age"
                                                    className="w-full"
                                                />
                                            </div>
                                        ) : (
                                            <div className="flex-grow">
                                                <InputLabel htmlFor={`birthdate-${index}`} value="Birthdate" className="sr-only" />
                                                <TextInput
                                                    id={`birthdate-${index}`}
                                                    type="date"
                                                    value={participant.birthdate}
                                                    onChange={(e) => updateParticipant(index, 'birthdate', e.target.value)}
                                                    className="w-full"
                                                />
                                            </div>
                                        )}

                                        {/* Age badge preview */}
                                        {validationMode === 'age' && participant.age && (
                                            <div className="flex-shrink-0">
                                                {getParticipantBadge(parseInt(participant.age, 10))}
                                            </div>
                                        )}

                                        {/* Remove button */}
                                        <button
                                            type="button"
                                            onClick={() => removeParticipant(index)}
                                            disabled={participants.length === 1}
                                            className="flex-shrink-0 p-2 text-red-600 hover:text-red-800 disabled:text-gray-300 disabled:cursor-not-allowed"
                                        >
                                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                ))}
                            </div>

                            {/* Action Buttons */}
                            <div className="flex justify-between mt-6">
                                <SecondaryButton type="button" onClick={addParticipant}>
                                    + Add Participant
                                </SecondaryButton>
                                
                                <div className="space-x-3">
                                    <SecondaryButton type="button" onClick={resetForm}>
                                        Reset
                                    </SecondaryButton>
                                    <PrimaryButton type="button" onClick={validateTeam} disabled={loading}>
                                        {loading ? 'Validating...' : 'Validate Team'}
                                    </PrimaryButton>
                                </div>
                            </div>

                            {/* Error Display */}
                            {error && (
                                <div className="mt-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                                    <InputError message={error} />
                                </div>
                            )}

                            {/* Result Display */}
                            {result && (
                                <div className={`mt-6 p-6 rounded-lg border-2 ${
                                    result.valid 
                                        ? 'bg-green-50 border-green-300' 
                                        : 'bg-red-50 border-red-300'
                                }`}>
                                    <div className="flex items-center mb-4">
                                        {result.valid ? (
                                            <>
                                                <svg className="w-8 h-8 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span className="text-xl font-semibold text-green-800">
                                                    {messages.team_valid || 'Team composition is valid!'}
                                                </span>
                                            </>
                                        ) : (
                                            <>
                                                <svg className="w-8 h-8 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span className="text-xl font-semibold text-red-800">
                                                    {messages.team_invalid || 'Team composition is invalid'}
                                                </span>
                                            </>
                                        )}
                                    </div>

                                    {/* Errors list */}
                                    {result.errors && result.errors.length > 0 && (
                                        <ul className="list-disc list-inside text-red-700 mb-4">
                                            {result.errors.map((err, i) => (
                                                <li key={i}>{err}</li>
                                            ))}
                                        </ul>
                                    )}

                                    {/* Calculated ages (for birthdate mode) */}
                                    {result.calculated_ages && (
                                        <div className="mt-4 pt-4 border-t border-gray-200">
                                            <h4 className="font-semibold text-gray-700 mb-2">Calculated Ages:</h4>
                                            <div className="flex flex-wrap gap-2">
                                                {result.calculated_ages.map((age, i) => (
                                                    <span key={i} className="px-3 py-1 bg-white rounded-full text-sm font-medium text-gray-700 shadow-sm">
                                                        Participant {i + 1}: {age} years
                                                    </span>
                                                ))}
                                            </div>
                                        </div>
                                    )}

                                    {/* Details */}
                                    {result.details && result.valid && (
                                        <div className="mt-4 pt-4 border-t border-gray-200">
                                            <h4 className="font-semibold text-gray-700 mb-2">Team Composition:</h4>
                                            <div className="grid grid-cols-2 gap-4">
                                                <div>
                                                    <span className="text-sm text-gray-500">Minors (under {thresholds.intermediate}):</span>
                                                    <span className="ml-2 font-semibold">{result.details.minors?.length || 0}</span>
                                                </div>
                                                <div>
                                                    <span className="text-sm text-gray-500">Adults ({thresholds.adult}+):</span>
                                                    <span className="ml-2 font-semibold">{result.details.adults?.length || 0}</span>
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
