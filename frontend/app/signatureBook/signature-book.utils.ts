import { Attachment, DocumentVersionsInterface } from "@models/attachment.model";
import { VisaCircuitActionObjectInterface } from "@models/actions.model";
import { SignatureBookContentHeader, SignatureBookContentHeaderInterface } from "@models/signature-book.model";

/**
 * Helper function to map attachment data
 * @param data
 * @returns Attachment
 */
export function mapAttachment(data: any): Attachment {
    return new Attachment({
        resId: data.resId,
        resIdMaster: data.resIdMaster ?? data.resId,
        signedResId: data.signedResId,
        chrono: data.chrono,
        title: data.title,
        type: data.type,
        typeLabel: data.typeLabel,
        canConvert: data.isConverted,
        canDelete: data.canDelete,
        canUpdate: data.canModify,
        hasDigitalSignature: data.hasDigitalSignature,
        stamps: [],
        isAttachment: data.resIdMaster !== null,
        externalDocumentId: data.externalDocumentId ?? null,
        fileInformation: data.creationDate ? mapFileInfo({ creationDate: data.creationDate, user: data.creator, version: data.version, format: data.originalFormat }) : null,
        signaturePositions: data.signaturePositions ?? [],
        annotations: data.annotations ?? null,
        versions:  sortVersionsByRelation(data.versions ?? []),
        isAnnotated: data.isAnnotated ?? false
    });
}

/**
 * Helper function to map content header data
 * @param data
 * @returns SignatureBookContentHeader
 */
export function mapFileInfo(data: { creationDate: string, user: { id: number, label: string }, version: number, format: string }): SignatureBookContentHeaderInterface {
    return new SignatureBookContentHeader ({
        typistId: data.user.id,
        typistLabel: data.user.label,
        creationDate: data.creationDate,
        format: data.format,
        version: data.version
    });
}

export function sortVersionsByRelation(versions: DocumentVersionsInterface[]): DocumentVersionsInterface[] {
    return versions.sort((a, b) => a.relation - b.relation);
}

/**
* Prepares and formats data for sending, specifically for documents to be rejected
* This function retrieves documents to be rejected, formats them, and then maps
* each document's data into a specific structure. It processes attachments and
* their associated metadata.
*
* @returns {
*   resId: number,
*   resIdMaster: number,
*   isAttachment: boolean,
*   documentId: string,
*   fileContentWithAnnotations: string
* }
* A promise that resolves to an object where keys are resource IDs and values are arrays of formatted attachment data
*/
export async function mapVisaCircuitActionDataToSend(data: VisaCircuitActionObjectInterface ): Promise<VisaCircuitActionObjectInterface> {
    Object.keys(data).forEach((resId: string) => {
        const mapData = (data[resId] as Attachment[]).map((resource: Attachment) => ({
            resId: resource.resId,
            resIdMaster: resource.resIdMaster,
            isAttachment: resource.isAttachment,
            documentId: resource.externalDocumentId,
            fileContentWithAnnotations: resource.fileContentWithAnnotations,
            isAnnotated: resource.isAnnotated
        }));
        data[resId] = mapData;
    });

    return data;
}
