import React from 'react';
import { Check } from 'lucide-react';

/**
 * Step indicator component displaying progress through registration steps.
 * 
 * @param {array} steps - Array of step objects with id and label
 * @param {number} currentStep - Current step index
 */
export default function StepIndicator({ steps, currentStep }) {
    return (
        <div className="flex items-center justify-center gap-2">
            {steps.map((step, index) => {
                const isCompleted = index < currentStep;
                const isCurrent = index === currentStep;

                return (
                    <React.Fragment key={step.id}>
                        {/* Step circle */}
                        <div
                            className={`
                                flex items-center justify-center w-8 h-8 rounded-full text-xs font-bold transition-all
                                ${isCompleted
                                    ? 'bg-emerald-500 text-white'
                                    : isCurrent
                                        ? 'bg-white text-blue-900'
                                        : 'bg-blue-700/50 text-blue-100/50'
                                }
                            `}
                        >
                            {isCompleted ? (
                                <Check className="h-4 w-4" />
                            ) : (
                                index + 1
                            )}
                        </div>

                        {/* Connector line */}
                        {index < steps.length - 1 && (
                            <div
                                className={`
                                    w-8 h-0.5 transition-all
                                    ${isCompleted ? 'bg-emerald-500' : 'bg-blue-700/50'}
                                `}
                            />
                        )}
                    </React.Fragment>
                );
            })}
        </div>
    );
}
