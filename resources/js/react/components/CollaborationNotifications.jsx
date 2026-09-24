import { useState, useEffect, useRef } from 'react';
import { Bell, Check, X, Loader2, FileText, User } from 'lucide-react';
import { getPendingInvitations, acceptInvitation, refuseInvitation } from '../services/CollaborationService';
import { toast } from 'sonner';
import { useNavigate } from 'react-router-dom';

export default function CollaborationNotifications() {
  const [invitations, setInvitations] = useState([]);
  const [isOpen, setIsOpen] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [processingIds, setProcessingIds] = useState(new Set());
  const dropdownRef = useRef(null);
  const navigate = useNavigate();

  useEffect(() => {
    fetchInvitations();
    // Refresh invitations every 30 seconds
    const interval = setInterval(fetchInvitations, 30000);
    return () => clearInterval(interval);
  }, []);

  // Close dropdown when clicking outside
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    };

    if (isOpen) {
      document.addEventListener('mousedown', handleClickOutside);
    }

    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [isOpen]);

  const fetchInvitations = async () => {
    try {
      setIsLoading(true);
      const response = await getPendingInvitations();
      if (response.data.status) {
        setInvitations(response.data.data || []);
      }
    } catch (error) {
    } finally {
      setIsLoading(false);
    }
  };

  const handleAccept = async (invitationId, resumeId) => {
    if (processingIds.has(invitationId)) return;

    try {
      setProcessingIds(prev => new Set(prev).add(invitationId));
      const response = await acceptInvitation(invitationId);
      if (response.data.status) {
        toast.success('Invitation accepted! You can now edit this resume.');
        setInvitations(prev => prev.filter(inv => inv.id !== invitationId));
        // Refresh resumes list by navigating to resumes page
        navigate('/resumes');
      }
    } catch (error) {
      toast.error(error.response?.data?.message || 'Failed to accept invitation');
    } finally {
      setProcessingIds(prev => {
        const newSet = new Set(prev);
        newSet.delete(invitationId);
        return newSet;
      });
    }
  };

  const handleRefuse = async (invitationId) => {
    if (processingIds.has(invitationId)) return;

    try {
      setProcessingIds(prev => new Set(prev).add(invitationId));
      const response = await refuseInvitation(invitationId);
      if (response.data.status) {
        toast.success('Invitation declined');
        setInvitations(prev => prev.filter(inv => inv.id !== invitationId));
      }
    } catch (error) {
      toast.error(error.response?.data?.message || 'Failed to decline invitation');
    } finally {
      setProcessingIds(prev => {
        const newSet = new Set(prev);
        newSet.delete(invitationId);
        return newSet;
      });
    }
  };

  const formatDate = (dateString) => {
    if (!dateString) return '';
    try {
      const date = new Date(dateString);
      const now = new Date();
      const diffInHours = Math.floor((now - date) / (1000 * 60 * 60));
      
      if (diffInHours < 1) return 'Just now';
      if (diffInHours < 24) return `${diffInHours}h ago`;
      if (diffInHours < 48) return 'Yesterday';
      
      const diffInDays = Math.floor(diffInHours / 24);
      if (diffInDays < 7) return `${diffInDays}d ago`;
      
      return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    } catch (error) {
      return '';
    }
  };

  const unreadCount = invitations.length;

  return (
    <div className="relative" ref={dropdownRef}>
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="relative p-2 text-gray-700 hover:text-blue-600 hover:bg-gray-100 rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
        title="Collaboration invitations"
      >
        <Bell className="h-5 w-5" />
        {unreadCount > 0 && (
          <span className="absolute top-0 right-0 flex items-center justify-center h-5 w-5 bg-red-500 text-white text-xs font-bold rounded-full transform translate-x-1/2 -translate-y-1/2">
            {unreadCount > 9 ? '9+' : unreadCount}
          </span>
        )}
      </button>

      {isOpen && (
        <>
          <div 
            className="fixed inset-0 z-10" 
            onClick={() => setIsOpen(false)}
          ></div>
          <div className="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-xl border border-gray-200 z-20 max-h-[600px] overflow-hidden flex flex-col">
            {/* Header */}
            <div className="px-4 py-3 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-purple-50">
              <div className="flex items-center justify-between">
                <h3 className="text-lg font-semibold text-gray-900">Collaboration Invitations</h3>
                {unreadCount > 0 && (
                  <span className="px-2 py-1 bg-red-500 text-white text-xs font-bold rounded-full">
                    {unreadCount} new
                  </span>
                )}
              </div>
            </div>

            {/* Content */}
            <div className="overflow-y-auto flex-1">
              {isLoading ? (
                <div className="flex items-center justify-center py-12">
                  <Loader2 className="h-6 w-6 animate-spin text-blue-600" />
                </div>
              ) : invitations.length === 0 ? (
                <div className="px-4 py-12 text-center">
                  <Bell className="h-12 w-12 text-gray-300 mx-auto mb-3" />
                  <p className="text-gray-500 text-sm">No pending invitations</p>
                </div>
              ) : (
                <div className="divide-y divide-gray-100">
                  {invitations.map((invitation) => {
                    const isProcessing = processingIds.has(invitation.id);
                    const resume = invitation.resume;
                    const owner = resume?.owner;

                    return (
                      <div
                        key={invitation.id}
                        className="p-4 hover:bg-gray-50 transition-colors duration-150"
                      >
                        <div className="flex items-start space-x-3">
                          <div className="flex-shrink-0">
                            <div className="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                              <FileText className="h-5 w-5 text-white" />
                            </div>
                          </div>
                          <div className="flex-1 min-w-0">
                            <div className="flex items-start justify-between">
                              <div className="flex-1">
                                <p className="text-sm font-semibold text-gray-900 truncate">
                                  {resume?.name || 'Untitled Resume'}
                                </p>
                                <div className="flex items-center mt-1 text-xs text-gray-500">
                                  <User className="h-3 w-3 mr-1" />
                                  <span className="truncate">
                                    {owner?.name || owner?.email || 'Unknown'}
                                  </span>
                                </div>
                                <p className="text-xs text-gray-400 mt-1">
                                  {formatDate(invitation.invited_at)}
                                </p>
                                {invitation.allowed_sections && invitation.allowed_sections.length > 0 && (
                                  <p className="text-xs text-blue-600 mt-1">
                                    Can edit: {invitation.allowed_sections.length} section(s)
                                  </p>
                                )}
                              </div>
                            </div>
                            <div className="flex items-center gap-2 mt-3">
                              <button
                                onClick={() => handleAccept(invitation.id, resume?.id)}
                                disabled={isProcessing}
                                className="flex-1 inline-flex items-center justify-center px-3 py-1.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                              >
                                {isProcessing ? (
                                  <Loader2 className="h-4 w-4 animate-spin" />
                                ) : (
                                  <>
                                    <Check className="h-4 w-4 mr-1" />
                                    Accept
                                  </>
                                )}
                              </button>
                              <button
                                onClick={() => handleRefuse(invitation.id)}
                                disabled={isProcessing}
                                className="flex-1 inline-flex items-center justify-center px-3 py-1.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                              >
                                {isProcessing ? (
                                  <Loader2 className="h-4 w-4 animate-spin" />
                                ) : (
                                  <>
                                    <X className="h-4 w-4 mr-1" />
                                    Decline
                                  </>
                                )}
                              </button>
                            </div>
                          </div>
                        </div>
                      </div>
                    );
                  })}
                </div>
              )}
            </div>
          </div>
        </>
      )}
    </div>
  );
}

