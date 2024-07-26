<?php


namespace constant;


class TencentAuditConstant
{
    // 腾讯判定的审核结果 0（审核正常），1 （判定为违规敏感文件），2（疑似敏感，建议人工复核）
    const TENCENT_AUDIT_RETURN_ERR = 1;

    // 0：未冻结，1：已被冻结，2：已转移文件
    const TENCENT_AUDIT_FORBIDDEN_ERR = 0;
}